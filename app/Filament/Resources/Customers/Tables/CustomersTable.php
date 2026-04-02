<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Exports\CustomersExport;
use App\Imports\CustomersImport;
use App\Models\Customer;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        $user         = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $isSuperAdmin
                ? $query
                : $query->where('company_id', $user?->company_id)
            )
            ->columns([
                ImageColumn::make('photo')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(
                        fn ($record) => 'https://ui-avatars.com/api/?'
                            . http_build_query([
                                'name'       => $record->names ?? 'C',
                                'background' => '0ea5e9',
                                'color'      => 'fff',
                                'bold'       => 'true',
                                'size'       => '80',
                            ])
                    )
                    ->size(42),

                TextColumn::make('names')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record) => $record->national_id
                        ? 'ID: ' . $record->national_id
                        : '—'
                    )
                    ->icon('heroicon-o-user')
                    ->iconColor('primary'),

                TextColumn::make('phone')
                    ->label('Contact')
                    ->searchable()
                    ->description(fn ($record) => $record->email ?? '—')
                    ->icon('heroicon-o-phone')
                    ->iconColor('gray')
                    ->copyable()
                    ->copyMessage('Phone copied')
                    ->copyMessageDuration(1500),

                TextColumn::make('date_of_birth')
                    ->label('Age')
                    ->date('M j, Y')
                    ->description(fn ($record) => $record->date_of_birth
                        ? \Carbon\Carbon::parse($record->date_of_birth)->age . ' years old'
                        : '—'
                    )
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('gender')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'male'   => 'info',
                        'female' => 'pink',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? '—')),

                TextColumn::make('marital_status')
                    ->label('Marital')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'single'   => 'gray',
                        'married'  => 'success',
                        'divorced' => 'warning',
                        'widowed'  => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? '—')),

                TextColumn::make('employment_status')
                    ->label('Employment')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'employed'      => 'success',
                        'self_employed' => 'info',
                        'unemployed'    => 'danger',
                        'retired'       => 'warning',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'self_employed' => 'Self Employed',
                        default         => ucfirst($state ?? '—'),
                    }),

                TextColumn::make('district')
                    ->label('Location')
                    ->searchable()
                    ->description(fn ($record) => collect([
                        $record->sector,
                        $record->province,
                    ])->filter()->implode(', ') ?: '—')
                    ->icon('heroicon-o-map-pin')
                    ->iconColor('gray'),

                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-office')
                    ->iconColor('gray')
                    ->placeholder('—')
                    ->visible($isSuperAdmin),

                TextColumn::make('is_active')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state
                        ? 'heroicon-o-check-badge'
                        : 'heroicon-o-x-circle'
                    ),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, Y')
                    ->description(fn ($record) => $record->created_at?->diffForHumans())
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->iconColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All customers')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                SelectFilter::make('gender')
                    ->label('Gender')
                    ->options([
                        'male'   => 'Male',
                        'female' => 'Female',
                        'other'  => 'Other',
                    ]),

                SelectFilter::make('marital_status')
                    ->label('Marital Status')
                    ->options([
                        'single'   => 'Single',
                        'married'  => 'Married',
                        'divorced' => 'Divorced',
                        'widowed'  => 'Widowed',
                    ]),

                SelectFilter::make('employment_status')
                    ->label('Employment')
                    ->options([
                        'employed'      => 'Employed',
                        'self_employed' => 'Self Employed',
                        'unemployed'    => 'Unemployed',
                        'retired'       => 'Retired',
                    ]),

                SelectFilter::make('province')
                    ->label('Province')
                    ->options(fn () => Customer::query()
                        ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $user?->company_id))
                        ->whereNotNull('province')
                        ->distinct()
                        ->pluck('province', 'province')
                        ->toArray()
                    )
                    ->searchable(),

                SelectFilter::make('company')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->visible($isSuperAdmin),
            ])

            ->headerActions([

                // ── ✅ Import Customers ───────────────────────────────────────
                Action::make('import_customers')
                    ->label('Import')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->button()
                    ->form([
                        Placeholder::make('template_info')
                            ->label('Step 1 — Download the template')
                            ->content(new \Illuminate\Support\HtmlString('
                                <a href="' . asset('templates/customers-import-template.xlsx') . '"
                                   class="inline-flex items-center gap-1 text-primary-600 underline text-sm font-medium"
                                   download>
                                   ⬇ Download Import Template (.xlsx)
                                </a>
                                <p class="text-xs text-gray-500 mt-1">
                                    Fill the template then upload below.
                                    <strong>Full Name</strong> is required.
                                    National ID skips duplicates.
                                </p>
                            ')),

                        Select::make('heading_row')
                            ->label('Which row has the column headers?')
                            ->options([
                                '1' => 'Row 1 — headers are on the very first row',
                                '2' => 'Row 2 — one title row above headers',
                                '3' => 'Row 3 — two rows above headers (default template)',
                            ])
                            ->default('3')
                            ->required()
                            ->helperText('Use Row 3 if you are using the downloaded template.'),

                        FileUpload::make('file')
                            ->label('Step 2 — Upload filled file')
                            ->disk('local')
                            ->directory('imports/customers')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                                'application/csv',
                            ])
                            ->required()
                            ->helperText('Accepted: .xlsx, .xls, .csv'),
                    ])
                    ->modalHeading('Import Customers')
                    ->modalDescription('Upload your filled Excel or CSV file to bulk-import customers.')
                    ->modalIcon('heroicon-o-arrow-up-tray')
                    ->modalSubmitActionLabel('Import Now')
                    ->action(function (array $data) use ($user) {

                        $relativePath = $data['file'];
                        $fullPath     = Storage::disk('local')->path($relativePath);
                        $headingRow   = (int) ($data['heading_row'] ?? 3);

                        if (! file_exists($fullPath)) {
                            Notification::make()
                                ->title('File not found')
                                ->body('The uploaded file could not be located. Please try again.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $countBefore = Customer::where('company_id', $user?->company_id)->count();

                        try {
                            $import = new CustomersImport($user?->company_id, $headingRow);
                            Excel::import($import, $fullPath);

                            $countAfter   = Customer::where('company_id', $user?->company_id)->count();
                            $realImported = $countAfter - $countBefore;

                        } catch (\Exception $e) {
                            Storage::disk('local')->delete($relativePath);
                            Notification::make()
                                ->title('Import failed')
                                ->body('Error: ' . $e->getMessage())
                                ->danger()
                                ->send();
                            return;
                        }

                        Storage::disk('local')->delete($relativePath);

                        // ── Result notification ───────────────────────────────
                        if ($realImported > 0) {

                            $body = "✅ {$realImported} customer(s) added to the database.";

                            if ($import->skippedCount > 0) {
                                $body .= "\n⏭ {$import->skippedCount} row(s) skipped (duplicates or empty).";
                            }

                            if (! empty($import->errors)) {
                                $body .= "\n⚠️ Issues:\n" . implode("\n", array_slice($import->errors, 0, 3));
                            }

                            Notification::make()
                                ->title('Import successful!')
                                ->body($body)
                                ->success()
                                ->send();

                        } else {
                            // Show detected keys to help diagnose
                            $keys = ! empty($import->detectedKeys)
                                ? 'Detected column keys: ' . implode(', ', array_slice($import->detectedKeys, 0, 8))
                                : 'No rows were read from the file.';

                            $errors = ! empty($import->errors)
                                ? "\n\nRow errors:\n" . implode("\n", array_slice($import->errors, 0, 5))
                                : '';

                            Notification::make()
                                ->title('Nothing imported — ' . $import->skippedCount . ' rows skipped')
                                ->body(
                                    "The importer could not find the customer name column.\n\n" .
                                    $keys .
                                    "\n\nExpected one of: full_name, names, name, customer_name" .
                                    $errors
                                )
                                ->warning()
                                ->persistent()
                                ->send();
                        }
                    }),

                // ── Export ────────────────────────────────────────────────────
                ExportAction::make()
                    ->exports([
                        CustomersExport::make('customers'),
                    ]),
            ])

            ->recordActions([
                ViewAction::make()->iconButton()->tooltip('View customer'),
                EditAction::make()->iconButton()->tooltip('Edit customer'),
                DeleteAction::make()->iconButton()->tooltip('Delete customer'),
            ])

            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('60s')
            ->emptyStateIcon('heroicon-o-users')
            ->emptyStateHeading('No customers yet')
            ->emptyStateDescription('Once customers are added, they will appear here.');
    }
}
