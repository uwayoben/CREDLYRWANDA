<?php

namespace App\Filament\Resources\OtherIncomes\Tables;

use App\Models\OtherIncome;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class OtherIncomesTable
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
                TextColumn::make('income_number')
                    ->label('Income')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record) => $record->income_date?->format('M j, Y') ?? '—')
                    ->icon('heroicon-o-hashtag')
                    ->iconColor('primary'),

                TextColumn::make('source')
                    ->label('Source')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->source)
                    ->description(fn ($record) => $record->reference_number
                        ? 'Ref: ' . $record->reference_number
                        : '—'
                    )
                    ->icon('heroicon-o-building-office')
                    ->iconColor('gray'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->money('RWF')
                    ->weight('semibold')
                    ->color('success'),

                TextColumn::make('payment_method')
                    ->label('Payment')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'cash'          => 'success',
                        'bank_transfer' => 'info',
                        'mobile_money'  => 'warning',
                        'cheque'        => 'gray',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'bank_transfer' => 'Bank Transfer',
                        'mobile_money'  => 'Mobile Money',
                        default         => ucfirst($state ?? '—'),
                    })
                    ->icon(fn ($state) => match ($state) {
                        'cash'          => 'heroicon-o-banknotes',
                        'bank_transfer' => 'heroicon-o-building-library',
                        'mobile_money'  => 'heroicon-o-device-phone-mobile',
                        'cheque'        => 'heroicon-o-document-text',
                        default         => 'heroicon-o-credit-card',
                    }),

                TextColumn::make('receivedBy.name')
                    ->label('Received By')
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->iconColor('gray')
                    ->toggleable(isToggledHiddenByDefault: false),

                // Super admin only: show which company the income belongs to
                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-office')
                    ->iconColor('gray')
                    ->placeholder('—')
                    ->visible($isSuperAdmin),

                TextColumn::make('created_at')
                    ->label('Created')
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
                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash'          => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'mobile_money'  => 'Mobile Money',
                        'cheque'        => 'Cheque',
                    ]),

                SelectFilter::make('source')
                    ->label('Source')
                    ->options(fn () => OtherIncome::query()
                        ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $user?->company_id))
                        ->whereNotNull('source')
                        ->distinct()
                        ->pluck('source', 'source')
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
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View income'),

                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit income'),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete income'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('income_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('60s')
            ->emptyStateIcon('heroicon-o-arrow-trending-up')
            ->emptyStateHeading('No income records yet')
            ->emptyStateDescription('Once income entries are recorded, they will appear here.');
    }
}
