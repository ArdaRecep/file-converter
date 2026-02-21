<?php

namespace App\Jobs;

use App\Models\ConversionFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Imagick;

class ConvertFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $conversionFileId) {}

    public function handle(): void
    {
        $file       = ConversionFile::with('conversion')->findOrFail($this->conversionFileId);
        $conversion = $file->conversion;

        $file->update(['status' => 'processing', 'error' => null]);

        try {
            $category = strtolower((string) $conversion->source_type);
            $target   = strtolower((string) $conversion->target_type);

            $inputExt = strtolower(pathinfo((string) $file->input_path, PATHINFO_EXTENSION));
            if ($inputExt === 'jpeg') $inputExt = 'jpg';

            if ($category === 'document') {
                $this->convertWithLibreOffice($file, $target);
            } elseif ($category === 'image') {
                $this->convertWithImagick($file, $target, $inputExt);
            } else {
                throw new \RuntimeException("Unsupported source_type {$category}");
            }

            $file->update(['status' => 'done']);
        } catch (\Throwable $e) {
            $file->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }
    }

    private function convertWithLibreOffice(ConversionFile $file, string $target): void
    {
        if (!in_array($target, ['pdf', 'html', 'txt'], true)) {
            throw new \RuntimeException("Unsupported target_type {$target}");
        }

        $conversionId = $file->conversion_id;
        $disk         = Storage::disk('local');

        $inAbs = $disk->path($file->input_path);

        $outDirRel = "conversions/{$conversionId}/output";
        $outDirAbs = $disk->path($outDirRel);
        if (!is_dir($outDirAbs)) @mkdir($outDirAbs, 0775, true);

        $before = glob($outDirAbs . '/*.' . $target) ?: [];

        $cmd = sprintf(
            'soffice --headless --nologo --nofirststartwizard --convert-to %s --outdir %s %s 2>&1',
            escapeshellarg($target),
            escapeshellarg($outDirAbs),
            escapeshellarg($inAbs)
        );

        $output = shell_exec($cmd) ?? '';

        $after    = glob($outDirAbs . '/*.' . $target) ?: [];
        $newFiles = array_values(array_diff($after, $before));

        if (count($newFiles) < 1) {
            throw new \RuntimeException('LibreOffice conversion failed: ' . trim($output));
        }

        usort($newFiles, fn($a, $b) => filemtime($b) <=> filemtime($a));
        $outAbs = $newFiles[0];

        $file->output_path = $outDirRel . '/' . basename($outAbs);
        $file->save();
    }

    private function convertWithImagick(ConversionFile $file, string $target, string $inputExt): void
    {
        if (!in_array($target, ['jpg', 'png', 'webp'], true)) {
            throw new \RuntimeException("Unsupported target_type {$target}");
        }

        if (!in_array($inputExt, ['jpg', 'png', 'webp'], true)) {
            throw new \RuntimeException("Unsupported input image type {$inputExt}");
        }

        if ($inputExt === $target) {
            throw new \RuntimeException("Same-format conversion is not allowed: {$inputExt} -> {$target}");
        }

        $conversionId = $file->conversion_id;
        $disk         = Storage::disk('local');

        $inAbs = $disk->path($file->input_path);

        $outDirRel = "conversions/{$conversionId}/output";
        $outDirAbs = $disk->path($outDirRel);
        if (!is_dir($outDirAbs)) @mkdir($outDirAbs, 0775, true);

        $base   = pathinfo($inAbs, PATHINFO_FILENAME);
        $outAbs = $outDirAbs . '/' . $base . '.' . $target;

        $img = new Imagick($inAbs);
        $img->setImageFormat($target === 'jpg' ? 'jpeg' : $target);
        $img->setImageCompressionQuality(85);
        $img->writeImage($outAbs);
        $img->clear();
        $img->destroy();

        if (!is_file($outAbs)) {
            throw new \RuntimeException('Imagick conversion failed');
        }

        $file->output_path = $outDirRel . '/' . basename($outAbs);
        $file->save();
    }
}