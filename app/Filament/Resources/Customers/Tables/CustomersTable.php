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

                // ── Avatar ────────────────────────────────────────────────────
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
                    ->size(36),

                // ── Customer Name + National ID ───────────────────────────────
                TextColumn::make('names')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->size('sm')
                    ->description(fn ($record) => $record->national_id
                        ? '🪪 ' . $record->national_id
                        : '—'
                    )
                    ->icon('heroicon-m-user-circle')
                    ->iconColor('primary'),

                // ── Phone + Email ─────────────────────────────────────────────
                TextColumn::make('phone')
                    ->label('Contact')
                    ->searchable()
                    ->size('sm')
                    ->description(fn ($record) => $record->email
                        ? '✉️ ' . $record->email
                        : '—'
                    )
                    ->icon('heroicon-m-phone')
                    ->iconColor('success')
                    ->copyable()
                    ->copyMessage('Phone copied!')
                    ->copyMessageDuration(1500),

                // ── Age ───────────────────────────────────────────────────────
                TextColumn::make('date_of_birth')
                    ->label('Age')
                    ->size('sm')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state
                        ? \Carbon\Carbon::parse($state)->age . ' yrs'
                        : '—'
                    )
                    ->tooltip(fn ($record) => $record->date_of_birth
                        ? \Carbon\Carbon::parse($record->date_of_birth)->format('d M Y')
                        : null
                    )
                    ->sortable()
                    ->toggleable(),

                // ── Gender ────────────────────────────────────────────────────
                TextColumn::make('gender')
                    ->label('Gender')
                    ->size('sm')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'male'   => 'info',
                        'female' => 'pink',
                        default  => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'male'   => 'heroicon-m-user',
                        'female' => 'heroicon-m-user',
                        default  => 'heroicon-m-user',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'male'   => '♂ Male',
                        'female' => '♀ Female',
                        default  => ucfirst($state ?? '—'),
                    }),

                // ── Marital Status ────────────────────────────────────────────
                TextColumn::make('marital_status')
                    ->label('Marital')
                    ->size('sm')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'single'   => 'gray',
                        'married'  => 'success',
                        'divorced' => 'warning',
                        'widowed'  => 'danger',
                        default    => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? '—')),

                // ── Employment ────────────────────────────────────────────────
                TextColumn::make('employment_status')
                    ->label('Employment')
                    ->size('sm')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'employed'      => 'success',
                        'self_employed' => 'info',
                        'unemployed'    => 'danger',
                        'retired'       => 'warning',
                        default         => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'employed'      => 'heroicon-m-briefcase',
                        'self_employed' => 'heroicon-m-building-storefront',
                        'unemployed'    => 'heroicon-m-x-circle',
                        'retired'       => 'heroicon-m-academic-cap',
                        default         => 'heroicon-m-minus-circle',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'self_employed' => 'Self Emp.',
                        default         => ucfirst($state ?? '—'),
                    }),

                // ── Location ──────────────────────────────────────────────────
                TextColumn::make('district')
                    ->label('Location')
                    ->searchable()
                    ->size('sm')
                    ->description(fn ($record) => collect([
                        $record->sector,
                        $record->province,
                    ])->filter()->implode(' · ') ?: '—')
                    ->icon('heroicon-m-map-pin')
                    ->iconColor('warning'),

                // ── Status ────────────────────────────────────────────────────
                TextColumn::make('is_active')
                    ->label('Status')
                    ->size('sm')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state
                        ? 'heroicon-m-check-circle'
                        : 'heroicon-m-x-circle'
                    ),

                // ── Company (super admin only) ────────────────────────────────
                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->size('sm')
                    ->badge()
                    ->color('primary')
                    ->icon('heroicon-m-building-office')
                    ->placeholder('—')
                    ->visible($isSuperAdmin),

                // ── Registered ────────────────────────────────────────────────
                TextColumn::make('created_at')
                    ->label('Registered')
                    ->size('sm')
                    ->since()
                    ->sortable()
                    ->tooltip(fn ($record) => $record->created_at?->format('d M Y, H:i'))
                    ->icon('heroicon-m-calendar')
                    ->iconColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            // ── Filters ───────────────────────────────────────────────────────
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All customers')
                    ->trueLabel('✅ Active only')
                    ->falseLabel('❌ Inactive only'),

                SelectFilter::make('gender')
                    ->label('Gender')
                    ->options([
                        'male'   => '♂ Male',
                        'female' => '♀ Female',
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
            ->filtersFormColumns(2)

            // ── Header Actions ────────────────────────────────────────────────
            ->headerActions([

                // Import
                Action::make('import_customers')
                    ->label('Import')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->button()
                    ->form([
                        Placeholder::make('template_info')
                            ->label('📋 Download the template first')
                            ->content(new \Illuminate\Support\HtmlString('
                                <div class="space-y-2">
                                    <a href="' . asset('templates/customers-import-template.xlsx') . '"
                                       class="inline-flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-white bg-primary-600 rounded-lg hover:bg-primary-700 transition"
                                       download>
                                       ⬇ Download Import Template (.xlsx)
                                    </a>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Fill the template then upload below.
                                        <strong>Full Names</strong> is required.
                                        National ID prevents duplicates globally.
                                    </p>
                                </div>
                            ')),

                        FileUpload::make('file')
                            ->label('📂 Upload filled file')
                            ->disk('local')
                            ->directory('imports/customers')
                            ->acceptedFileTypes([
                                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                'application/vnd.ms-excel',
                                'text/csv',
                                'application/csv',
                            ])
                            ->required()
                            ->helperText('Accepted: .xlsx, .xls, .csv — headers must be on Row 1'),
                    ])
                    ->modalHeading('Import Customers')
                    ->modalDescription('Bulk-import customers from an Excel or CSV file. Headers on Row 1.')
                    ->modalIcon('heroicon-o-arrow-up-tray')
                    ->modalSubmitActionLabel('Import Now')
                    ->action(function (array $data) use ($user) {
                        $relativePath = $data['file'];
                        $fullPath     = Storage::disk('local')->path($relativePath);

                        if (! file_exists($fullPath)) {
                            Notification::make()->title('File not found')->body('Please try again.')->danger()->send();
                            return;
                        }

                        $countBefore = Customer::where('company_id', $user?->company_id)->count();

                        try {
                            $import = new CustomersImport($user?->company_id);
                            Excel::import($import, $fullPath);

                            $countAfter   = Customer::where('company_id', $user?->company_id)->count();
                            $realImported = $countAfter - $countBefore;

                        } catch (\Exception $e) {
                            Storage::disk('local')->delete($relativePath);
                            Notification::make()->title('Import failed')->body('Error: ' . $e->getMessage())->danger()->send();
                            return;
                        }

                        Storage::disk('local')->delete($relativePath);

                        if ($realImported > 0) {
                            $body = "✅ {$realImported} customer(s) added.";
                            if ($import->skippedCount > 0) $body .= "\n⏭ {$import->skippedCount} skipped (duplicates or empty).";
                            if (! empty($import->errors)) $body .= "\n⚠️ " . implode("\n", array_slice($import->errors, 0, 3));
                            Notification::make()->title('Import successful!')->body($body)->success()->send();
                        } else {
                            $keys   = ! empty($import->detectedKeys) ? implode(', ', array_slice($import->detectedKeys, 0, 6)) : 'No rows read.';
                            $errors = ! empty($import->errors) ? "\n\n" . implode("\n", array_slice($import->errors, 0, 5)) : '';
                            Notification::make()
                                ->title('Nothing imported — ' . $import->skippedCount . ' rows skipped')
                                ->body("Detected keys: {$keys}{$errors}")
                                ->warning()->persistent()->send();
                        }
                    }),

                // Export
                ExportAction::make()
                    ->exports([
                        CustomersExport::make('customers'),
                    ]),
            ])

            // ── Row Actions ───────────────────────────────────────────────────
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View profile'),

                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit customer'),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete customer'),
            ])

            // ── Bulk Actions ──────────────────────────────────────────────────
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])

            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('60s')
            ->emptyStateIcon('heroicon-o-user-group')
            ->emptyStateHeading('No customers yet')
            ->emptyStateDescription('Import customers from Excel or add them one by one.')
            ->emptyStateActions([
                Action::make('import_empty')
                    ->label('Import Customers')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('info')
                    ->url('#'),

                Action::make('create_empty')
                    ->label('Add Customer')
                    ->icon('heroicon-o-plus')
                    ->color('primary')
                    ->url(fn () => route('filament.admin.resources.customers.create')),
            ]);
    }
}
