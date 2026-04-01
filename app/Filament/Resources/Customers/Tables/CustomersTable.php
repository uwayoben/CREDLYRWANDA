<?php

namespace App\Filament\Resources\Customers\Tables;

use App\Exports\CustomersExport;
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

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction;
use App\Filament\Actions\ImportCustomersAction;





class CustomersTable
{
    public static function configure(Table $table): Table
    {
        $user          = Auth::user();
        $isSuperAdmin  = $user?->is_super_admin ?? false;

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

                // Only super admins see which company a customer belongs to
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

                // Province filter scoped to the company's own data for non-super admins
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

                // Super admin only: filter by company
                SelectFilter::make('company')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->visible($isSuperAdmin),
            ])
            ->headerActions([
    Action::make('import_customers')
        ->label('Import')
        ->icon('heroicon-o-arrow-up-tray')
        ->color('info')
        ->form([
            \Filament\Forms\Components\Placeholder::make('template_info')
                ->label('Template')
                ->content(new \Illuminate\Support\HtmlString('
                    <a href="' . asset('templates/customers-import-template.xlsx') . '"
                       class="text-primary-600 underline text-sm"
                       download>
                       ⬇ Download Import Template
                    </a>
                ')),

            \Filament\Forms\Components\FileUpload::make('file')
                ->label('Excel / CSV File')
                ->acceptedFileTypes([
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/csv',
                ])
                ->required()
                ->helperText('Upload the filled template. Accepted: .xlsx, .csv'),
        ])
        ->action(function (array $data) {
            // import logic here
        }),

    ExportAction::make()
        ->exports([
            CustomersExport::make('customers'),
        ]),
])




                   ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View customer'),

                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit customer'),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete customer'),
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
