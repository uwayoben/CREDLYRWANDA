<?php

namespace App\Filament\Resources\Expenses\Tables;

use App\Models\Expense;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ExpensesTable
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
                TextColumn::make('expense_number')
                    ->label('Expense')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record) => $record->expense_date?->format('M j, Y') ?? '—')
                    ->icon('heroicon-o-hashtag')
                    ->iconColor('primary'),

                TextColumn::make('item')
                    ->label('Item')
                    ->searchable()
                    ->description(fn ($record) => $record->category
                        ? ucwords(str_replace('_', ' ', $record->category))
                        : '—'
                    )
                    ->icon('heroicon-o-shopping-bag')
                    ->iconColor('gray')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->item),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->numeric(decimalPlaces: 2)
                    ->sortable()
                    ->money('RWF')
                    ->weight('semibold')
                    ->color('danger'),

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

                TextColumn::make('is_recurring')
                    ->label('Recurring')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Recurring' : 'One-time')
                    ->color(fn ($state) => $state ? 'warning' : 'gray')
                    ->description(fn ($record) => $record->is_recurring && $record->recurring_frequency
                        ? ucfirst($record->recurring_frequency)
                        : null
                    )
                    ->icon(fn ($state) => $state
                        ? 'heroicon-o-arrow-path'
                        : 'heroicon-o-minus-circle'
                    ),

                TextColumn::make('createdBy.name')
                    ->label('Created By')
                    ->sortable()
                    ->icon('heroicon-o-user')
                    ->iconColor('gray')
                    ->toggleable(isToggledHiddenByDefault: false),

                // Super admin only: show which company the expense belongs to
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

                TextColumn::make('deleted_at')
                    ->label('Deleted')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->icon('heroicon-o-trash')
                    ->iconColor('danger')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),

                TernaryFilter::make('is_recurring')
                    ->label('Recurring')
                    ->placeholder('All expenses')
                    ->trueLabel('Recurring only')
                    ->falseLabel('One-time only'),

                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash'          => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'mobile_money'  => 'Mobile Money',
                        'cheque'        => 'Cheque',
                    ]),

                SelectFilter::make('category')
                    ->label('Category')
                    ->options([
                        'office_supplies' => 'Office Supplies',
                        'utilities'       => 'Utilities',
                        'salaries'        => 'Salaries',
                        'rent'            => 'Rent',
                        'transport'       => 'Transport',
                        'marketing'       => 'Marketing',
                        'maintenance'     => 'Maintenance',
                        'it_equipment'    => 'IT & Equipment',
                        'training'        => 'Training',
                        'miscellaneous'   => 'Miscellaneous',
                    ])
                    ->searchable(),

                SelectFilter::make('recurring_frequency')
                    ->label('Frequency')
                    ->options([
                        'daily'     => 'Daily',
                        'weekly'    => 'Weekly',
                        'monthly'   => 'Monthly',
                        'quarterly' => 'Quarterly',
                        'yearly'    => 'Yearly',
                    ]),

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
                    ->tooltip('View expense'),

                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit expense'),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete expense'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('expense_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('60s')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateHeading('No expenses yet')
            ->emptyStateDescription('Once expenses are recorded, they will appear here.');
    }
}
