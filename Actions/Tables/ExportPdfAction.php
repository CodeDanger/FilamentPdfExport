<?php
namespace App\Filament\Actions\Tables;

use Filament\Tables\Actions\Action;
use App\Jobs\ExportPdfJob;
use Filament\Forms;
use Illuminate\Contracts\Database\Eloquent\Builder;
use App\Filament\Actions\Concerns\CanExportRecords;

class ExportPdfAction extends Action
{
    use CanExportRecords;

}