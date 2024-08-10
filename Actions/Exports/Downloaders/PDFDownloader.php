<?php

namespace App\Filament\Actions\Exports\Downloaders;

use App\Filament\Actions\Exports\Downloaders\Contracts\Downloader;
use App\Filament\Actions\Exports\Models\Export;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PDFDownloader implements Downloader
{
    public function __invoke(Export $export): StreamedResponse
    {
        $disk = $export->getFileDisk();
        $directory = $export->getFileDirectory();
        // Check if the directory exists
        if (! $disk->exists($directory)) {
            abort(404);
        }

        // Find the PDF file
        $pdfFile = collect($disk->files($directory))->first(fn ($file) => str($file)->endsWith('.pdf'));

        if (! $pdfFile) {
            abort(404, 'PDF file not found.');
        }

        return response()->streamDownload(function () use ($disk, $pdfFile) {
            // Stream the PDF file content
            echo $disk->get($pdfFile);

            flush();
        }, "{$export->file_name}.pdf", [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
