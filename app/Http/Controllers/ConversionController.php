<?php

namespace App\Http\Controllers;

use App\Jobs\ConvertFileJob;
use App\Models\Conversion;
use App\Models\ConversionFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ConversionController extends Controller
{
    public function index()
    {
        return view('index');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'source_type' => 'required|in:document,image',
            'target_type' => 'required|string',
            'files'       => 'required|array|min:1|max:20',
            'files.*'     => 'file|max:25600',
        ]);

        $cfg            = config('conversions.categories');
        $sourceCategory = $data['source_type'];
        $target         = strtolower(trim($data['target_type']));

        if (!isset($cfg[$sourceCategory])) {
            return response()->json(['message' => 'Geçersiz kaynak tür'], 422);
        }

        $allowedTargets = $cfg[$sourceCategory]['targets'] ?? [];
        if (!in_array($target, $allowedTargets, true)) {
            return response()->json(['message' => 'Bu hedef format desteklenmiyor'], 422);
        }

        $allowedExts = array_map(
            fn($e) => $e === 'jpeg' ? 'jpg' : $e,
            $cfg[$sourceCategory]['accept'] ?? []
        );

        // Sadece kategoriye uygun olmayan uzantıları reddet
        foreach ($request->file('files') as $f) {
            $ext = strtolower($f->getClientOriginalExtension());
            if ($ext === 'jpeg') $ext = 'jpg';

            if (!in_array($ext, $allowedExts, true)) {
                return response()->json(['message' => "Bu dosya türü kabul edilmiyor: .{$ext}"], 422);
            }
        }

        $conversion = Conversion::create([
            'source_type' => $sourceCategory,
            'target_type' => $target,
            'status'      => 'queued',
        ]);

        $disk     = Storage::disk('local');
        $inputDir = "conversions/{$conversion->id}/input";

        $disk->makeDirectory($inputDir);
        $disk->makeDirectory("conversions/{$conversion->id}/output");

        foreach ($request->file('files') as $file) {
            $ext = strtolower($file->getClientOriginalExtension());
            if ($ext === 'jpeg') $ext = 'jpg';

            $stored = $file->store($inputDir, 'local');

            // Aynı formata dönüşüm — dispatch etme, direkt failed kaydet
            if ($sourceCategory === 'image' && $ext === $target) {
                ConversionFile::create([
                    'conversion_id' => $conversion->id,
                    'original_name' => $file->getClientOriginalName(),
                    'input_path'    => $stored,
                    'status'        => 'failed',
                    'error'         => "Aynı formata dönüşüm gereksiz: .{$ext} → .{$target}",
                ]);
                continue;
            }

            $row = ConversionFile::create([
                'conversion_id' => $conversion->id,
                'original_name' => $file->getClientOriginalName(),
                'input_path'    => $stored,
                'status'        => 'queued',
            ]);

            ConvertFileJob::dispatch($row->id);
        }

        return response()->json(['id' => $conversion->id]);
    }

    public function show(Conversion $conversion)
    {
        $total   = $conversion->files()->count();
        $done    = $conversion->files()->where('status', 'done')->count();
        $failed  = $conversion->files()->where('status', 'failed')->count();
        $pending = $total - $done - $failed;

        if ($pending > 0) {
            $status = 'processing';
        } elseif ($done > 0 && $failed === 0) {
            $status = 'done';
        } elseif ($done > 0 && $failed > 0) {
            $status = 'partial'; // bazı dosyalar başarılı, bazıları değil
        } else {
            $status = 'failed';
        }

        $conversion->status = $status;
        $conversion->save();

        $failedFiles = $conversion->files()
            ->where('status', 'failed')
            ->get(['original_name', 'error']);

        return response()->json([
            'id'           => $conversion->id,
            'status'       => $status,
            'total'        => $total,
            'done'         => $done,
            'failed'       => $failed,
            'failed_files' => $failedFiles,
        ]);
    }

    public function download(Conversion $conversion)
    {
        // done veya partial durumunda indir
        if (!in_array($conversion->status, ['done', 'partial'])) {
            abort(404);
        }

        $disk = Storage::disk('local');

        $zipDir = "conversions/{$conversion->id}";
        $zipRel = "{$zipDir}/Converted.zip";
        $zipAbs = $disk->path($zipRel);

        $disk->makeDirectory($zipDir);
        @unlink($zipAbs);

        $zip    = new ZipArchive();
        $opened = $zip->open($zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            abort(500, "Zip open failed: {$opened}");
        }

        $files = $conversion->files()->where('status', 'done')->get();

        $i = 1;
        foreach ($files as $f) {
            if (!$f->output_path) continue;

            $outAbs = $disk->path($f->output_path);
            if (!is_file($outAbs)) continue;

            $base = pathinfo($f->original_name, PATHINFO_FILENAME);
            $ext  = $conversion->target_type;
            $name = "Converted-{$base}-{$i}.{$ext}";

            $zip->addFile($outAbs, $name);
            $i++;
        }

        $zip->close();

        return response()->download($zipAbs)->deleteFileAfterSend(true);
    }
}