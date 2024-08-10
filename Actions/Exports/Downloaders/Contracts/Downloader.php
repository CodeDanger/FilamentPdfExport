<?php

namespace App\Filament\Actions\Exports\Downloaders\Contracts;

use App\Filament\Actions\Exports\Models\Export;
use Symfony\Component\HttpFoundation\StreamedResponse;

interface Downloader
{
    public function __invoke(Export $export): StreamedResponse;
}
