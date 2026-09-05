<?php

namespace App\Jobs;

use App\Models\ProductImportBatch;
use App\Services\PdfProductOcrImportService;
use App\Services\ProductImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ProcessProductImportBatch implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 1;

    public function __construct(public int $batchId)
    {
    }

    public function handle(
        PdfProductOcrImportService $ocrImportService,
        ProductImportService $productImportService
    ): void {
        $batch = ProductImportBatch::with('store')->findOrFail($this->batchId);

        if ($batch->status === ProductImportBatch::STATUS_IMPORTED) {
            return;
        }

        $batch->update([
            'status' => ProductImportBatch::STATUS_PROCESSING,
            'started_at' => now(),
            'error' => null,
        ]);

        try {
            $pdf = $this->uploadedFileFromStorage(
                (string) $batch->pdf_path,
                $batch->source_name ?: 'catalogo.pdf',
                'application/pdf'
            );
            $zip = $batch->zip_path
                ? $this->uploadedFileFromStorage(
                    (string) $batch->zip_path,
                    $batch->zip_original_name ?: 'imagenes.zip',
                    'application/zip'
                )
                : null;

            $rows = $ocrImportService->extractRows(
                $batch->store,
                $pdf,
                (int) config('services.openai.ocr_queue_timeout', 240),
                $batch->user_id ? (int) $batch->user_id : null
            );

            $preview = $productImportService->previewRows(
                $batch->store,
                $rows,
                'PDF con OCR + IA: ' . ($batch->source_name ?: 'catalogo.pdf'),
                $zip
            );

            $batch->update([
                'status' => ProductImportBatch::STATUS_COMPLETED,
                'preview' => $preview,
                'completed_at' => now(),
            ]);

            $this->deleteUploadFiles($batch);
        } catch (Throwable $exception) {
            $this->deleteUploadFiles($batch);

            $batch->update([
                'status' => ProductImportBatch::STATUS_FAILED,
                'error' => $exception->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    private function uploadedFileFromStorage(string $path, string $name, string $mimeType): UploadedFile
    {
        if ($path === '' || ! Storage::disk('local')->exists($path)) {
            throw new \RuntimeException('No encontramos el archivo subido para procesar.');
        }

        return new UploadedFile(
            Storage::disk('local')->path($path),
            $name,
            $mimeType,
            null,
            true
        );
    }

    private function deleteUploadFiles(ProductImportBatch $batch): void
    {
        foreach ([$batch->pdf_path, $batch->zip_path] as $path) {
            $path = (string) $path;

            if ($path !== '' && str_starts_with($path, 'imports/product-ocr/')) {
                Storage::disk('local')->delete($path);
            }
        }

        $batch->forceFill([
            'pdf_path' => null,
            'zip_path' => null,
        ])->save();
    }
}
