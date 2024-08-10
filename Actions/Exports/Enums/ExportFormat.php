<?php

namespace App\Filament\Actions\Exports\Enums;

use App\Filament\Actions\Exports\Downloaders\Contracts\Downloader;
use App\Filament\Actions\Exports\Downloaders\PDFDownloader;
use App\Filament\Actions\Exports\Downloaders\CsvDownloader;
use App\Filament\Actions\Exports\Downloaders\XlsxDownloader;
use App\Filament\Actions\Exports\Models\Export;
use Filament\Notifications\Actions\Action as NotificationAction;

enum ExportFormat: string
{
    case Csv = 'csv';

    case Xlsx = 'xlsx';

    case Pdf = 'pdf';

    public function getDownloader(): Downloader
    {
        return match ($this) {
            self::Csv => app(CsvDownloader::class),
            self::Xlsx => app(XlsxDownloader::class),
            self::Pdf => app(PDFDownloader::class),
        };
    }

    public function getDownloadNotificationAction(Export $export): NotificationAction
    {
        return NotificationAction::make("download_{$this->value}")
            ->label(str_contains($this->value,'pdf')?__("Download As Pdf"): __("filament-actions::export.notifications.completed.actions.download_{$this->value}.label"))
            ->url(route('filament.exports.download', ['export' => $export, 'format' => $this], absolute: false), shouldOpenInNewTab: true)
            ->markAsRead();
    }
}
