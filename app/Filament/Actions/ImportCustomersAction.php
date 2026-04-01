<?php

namespace App\Filament\Actions;

use App\Imports\CustomersImport;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Maatwebsite\Excel\Facades\Excel;

class ImportCustomersAction
{
    public static function make(): Action
    {
        return Action::make('import_customers')
            ->label('Import')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('info')
            ->form([
                FileUpload::make('file')
                    ->label('Excel / CSV File')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                        'text/plain',
                    ])
                    ->maxSize(5120)
                    ->required()
                    ->helperText('Upload .xlsx or .csv file. First row must be the header row.'),
            ])
            ->action(function (array $data) {
                $file = storage_path('app/public/' . $data['file']);

                try {
                    $import = new CustomersImport();
                    Excel::import($import, $file);

                    $errors = $import->errors();

                    if ($errors->count() > 0) {
                        Notification::make()
                            ->title('Import completed with errors')
                            ->body($errors->count() . ' row(s) were skipped due to errors.')
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Import successful')
                            ->body('Customers have been imported successfully.')
                            ->success()
                            ->send();
                    }
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Import failed')
                        ->body('Error: ' . $e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
