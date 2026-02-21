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
            'source_type' => 'required|in:docx,image',
            'target_type' => 'required|in:pdf,webp',
            'files' => 'required|array|min:1|max:20',
            'files.*' => 'file|max:25600',
        ]);

        if ($data['source_type'] === 'docx' && $data['target_type'] !== 'pdf') {
            return response()->json(['message' => 'DOCX sadece PDF olur'], 422);
        }
        if ($data['source_type'] === 'image' && $data['target_type'] !== 'webp') {
            return response()->json(['message' => 'Image sadece WEBP olur'], 422);
        }

        foreach ($request->file('files') as $f) {
            $ext = strtolower($f->getClientOriginalExtension());
            if ($data['source_type'] === 'docx' && $ext !== 'docx') {
                return response()->json(['message' => 'Sadece .docx yükleyin'], 422);
            }
            if ($data['source_type'] === 'image' && !in_array($ext, ['jpg', 'jpeg', 'png'], true)) {
                return response()->json(['message' => 'Sadece .jpg/.jpeg/.png yükleyin'], 422);
            }
        }

        $conversion = Conversion::create([
            'source_type' => $data['source_type'],
            'target_type' => $data['target_type'],
            'status' => 'queued',
        ]);

        $inputDir = "conversions/{$conversion->id}/input";
        $outputDir = "conversions/{$conversion->id}/output";
        $disk = Storage::disk('local');

        $disk->makeDirectory($inputDir);
        $disk->makeDirectory($outputDir);

        foreach ($request->file('files') as $file) {
            $stored = $file->store($inputDir, 'local');

            $row = ConversionFile::create([
                'conversion_id' => $conversion->id,
                'original_name' => $file->getClientOriginalName(),
                'input_path' => $stored,
                'status' => 'queued',
            ]);

            ConvertFileJob::dispatch($row->id);
        }

        return response()->json(['id' => $conversion->id]);
    }

    public function show(Conversion $conversion)
    {
        $total = $conversion->files()->count();
        $done = $conversion->files()->where('status', 'done')->count();
        $failed = $conversion->files()->where('status', 'failed')->count();

        if ($failed > 0)
            $conversion->status = 'failed';
        elseif ($total > 0 && $done === $total)
            $conversion->status = 'done';
        else
            $conversion->status = 'processing';

        $conversion->save();

        return response()->json([
            'id' => $conversion->id,
            'status' => $conversion->status,
            'total' => $total,
            'done' => $done,
            'failed' => $failed,
        ]);
    }

    public function download(Conversion $conversion)
    {
        if ($conversion->status !== 'done')
            abort(404);

        $disk = Storage::disk('local');

        $zipDir = "conversions/{$conversion->id}";
        $zipRel = "{$zipDir}/Converted.zip";
        $zipAbs = $disk->path($zipRel);

        $disk->makeDirectory($zipDir);
        @unlink($zipAbs);

        $zip = new ZipArchive();
        $opened = $zip->open($zipAbs, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            abort(500, "Zip open failed: {$opened}");
        }

        $files = $conversion->files()->where('status', 'done')->get();

        $i = 1;
        foreach ($files as $f) {
            if (!$f->output_path)
                continue;

            $outAbs = $disk->path($f->output_path);
            if (!is_file($outAbs))
                continue;

            $base = pathinfo($f->original_name, PATHINFO_FILENAME);
            $ext = $conversion->target_type;
            $name = "Converted-{$base}-{$i}.{$ext}";

            $zip->addFile($outAbs, $name);
            $i++;
        }

        $zip->close();

        return response()->download($zipAbs)->deleteFileAfterSend(true);
    }
}