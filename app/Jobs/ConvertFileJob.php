<?php

namespace App\Jobs;

use App\Models\ConversionFile;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Imagick;
use Illuminate\Support\Facades\Storage;

class ConvertFileJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $conversionFileId) {}

    public function handle(): void
    {
        $file = ConversionFile::with('conversion')->findOrFail($this->conversionFileId);
        $conversion = $file->conversion;

        $file->update(['status' => 'processing', 'error' => null]);

        try {
            if ($conversion->source_type === 'docx') $this->convertDocxToPdf($file);
            elseif ($conversion->source_type === 'image') $this->convertImageToWebp($file);
            else throw new \RuntimeException('Unsupported source_type');

            $file->update(['status' => 'done']);
        } catch (\Throwable $e) {
            $file->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }
    }

    private function convertDocxToPdf(ConversionFile $file): void
    {
        $conversionId = $file->conversion_id;

        $disk = Storage::disk('local');

        $inAbs = $disk->path($file->input_path);
        $outDirAbs = $disk->path("conversions/{$conversionId}/output");
        $outDirRel = "conversions/{$conversionId}/output";

        $cmd = sprintf(
            'soffice --headless --nologo --nofirststartwizard --convert-to pdf --outdir %s %s 2>&1',
            escapeshellarg($outDirAbs),
            escapeshellarg($inAbs)
        );

        $output = shell_exec($cmd) ?? '';

        $expectedPdf = $outDirAbs . '/' . pathinfo($inAbs, PATHINFO_FILENAME) . '.pdf';
        if (!is_file($expectedPdf)) {
            throw new \RuntimeException('LibreOffice conversion failed: ' . trim($output));
        }

        $file->output_path = $outDirRel . '/' . basename($expectedPdf);
        $file->save();
    }

    private function convertImageToWebp(ConversionFile $file): void
    {
        $conversionId = $file->conversion_id;

        $disk = Storage::disk('local');

        $inAbs = $disk->path($file->input_path);
        $outDirAbs = $disk->path("conversions/{$conversionId}/output");
        $outDirRel = "conversions/{$conversionId}/output";

        $base = pathinfo($inAbs, PATHINFO_FILENAME);
        $outAbs = $outDirAbs . '/' . $base . '.webp';

        $img = new Imagick($inAbs);
        $img->setImageFormat('webp');
        $img->setImageCompressionQuality(85);
        $img->writeImage($outAbs);
        $img->clear();
        $img->destroy();

        if (!is_file($outAbs)) throw new \RuntimeException('Imagick conversion failed');

        $file->output_path = $outDirRel . '/' . basename($outAbs);
        $file->save();
    }
}