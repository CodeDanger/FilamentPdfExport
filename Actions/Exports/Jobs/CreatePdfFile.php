<?php

namespace App\Filament\Actions\Exports\Jobs;

use App\Filament\Actions\Exports\Exporter;
use App\Filament\Actions\Exports\Models\Export;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\File;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Csv\Reader as CsvReader;
use League\Csv\Statement;
use Illuminate\Support\Facades\Log;
use Mpdf\Mpdf;
use Mpdf\MpdfException;

class CreatePdfFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public bool $deleteWhenMissingModels = true;

    protected Exporter $exporter;

    public function __construct(
        protected Export $export,
        protected array $columnMap,
        protected array $options = [],
    ) {
        $this->exporter = $this->export->getExporter(
            $this->columnMap,
            $this->options,
        );
    }

    public function handle(): void
    {
        try {
            $disk = $this->export->getFileDisk();
            $directory = $this->export->getFileDirectory();
            // In case no directory found
            if (!$disk->exists($directory)) {
                $disk->makeDirectory($directory);
            }
            // $disk->put($directory . '/' . "{$this->export->file_name}.pdf", '');
            $pdf = new Mpdf([
                'format' => 'A4-L',
                'default_font' => 'dejavusans',
            ]);
            $pdf->charset_in='UTF-8';
            $pdf->SetDirectionality(app()->getLocale()=="ar"?'rtl':'ltr');
           

            // Set up PDF margins and formatting
            $pdf->SetMargins(15, 15, 15);

            $files = $disk->files($this->export->getFileDirectory());
            $html='';  
            foreach ($this->exporter->getPDFPreviousContent() as  $value) {
                $html .= $value;
            } 
            $html .= '<table border="1" style="width: 100%; border-collapse: collapse;">';
            $counter=0;
            // Write content
            foreach (array_reverse($files) as $file) {
                if (!str($file)->endsWith('.csv')) {
                    continue;
                }
                $counter++;
                $isHeader = str($file)->contains('headers');
                $csvReader = CsvReader::createFromStream($disk->readStream($file));
                $csvResults = Statement::create()->process($csvReader);
                $records = $csvResults->getRecords(); 

                // set the file header style and footer style once  also previous content
                if($counter==1){
                    // Add header style
                    $headerStyle = $this->exporter->getPdfHeaderStyle($records);
                    if(!empty($headerStyle))
                    $pdf->SetHeader($headerStyle);
                    
                    // Add footer style
                    $footerStyle = $this->exporter->getPdfFooterStyle($records);
                    if(!empty($footerStyle))
                    $pdf->SetFooter($footerStyle);                           
                    // foreach ($this->exporter->getPDFPreviousContent($records) as  $value) {
                    //     $html .= $value;
                    // }
                }

                
                

                if($isHeader){
                    $html .= '<thead>';        
                }
                if($counter==1){
                    foreach ($this->exporter->getPDFTablePreviousContent($records) as  $value) {
                        $html .= $value;
                    }    
                }
                foreach ($records as $row) {
                    $html .= '<tr>';
                    foreach ($row as $cell) {
                        $html .= ($isHeader? '<th>':'<td>') . htmlspecialchars($cell) . ($isHeader? '</th>':'</td>');
                    }
                    $html .= '</tr>';
                }
                if($isHeader){
                    $html .= '</thead>';
                }

            }

            foreach ($this->exporter->getPDFTableAfterContent($records) as  $value) {
                $html .= $value;
            }
 
            $html .= '</tbody></table>';

            foreach ($this->exporter->getPDFAfterContent($records) as  $value) {
                $html .= $value;
            }
            $pdf->WriteHTML($html);
            $pdf->Output($disk->path($directory . '/' . "{$this->export->file_name}.pdf"), 'F');

        } catch (MpdfException $e) {
            // Log::error('PDF generation failed: ' . $e->getMessage(), [
            //     'exception' => $e,
            //     'export' => $this->export->toArray(),
            //     'columnMap' => $this->columnMap,
            //     'options' => $this->options,
            // ]);
            throw $e; // Rethrow to ensure the job fails and can be retried if necessary
        } catch (\Exception $e) {
            // Log::error('An unexpected error occurred: ' . $e->getMessage(), [
            //     'exception' => $e,
            //     'export' => $this->export->toArray(),
            //     'columnMap' => $this->columnMap,
            //     'options' => $this->options,
            // ]);
            throw $e; // Rethrow to ensure the job fails and can be retried if necessary
        }
    }
}
