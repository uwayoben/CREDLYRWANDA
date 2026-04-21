<?php

namespace App\Filament\Resources\Penalities\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PenalitiesTable
{
    public static function configure(Table $table): Table
    {
        $user         = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $isSuperAdmin
                ? $query
                : $query->whereHas('loan', fn ($q) => $q->where('company_id', $user?->company_id))
            )
            ->columns([
                TextColumn::make('loan.loan_number')
                    ->label('Loan')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-document-text')
                    ->iconColor('primary')
                    ->description(fn ($record) => $record->loan?->customer?->names ?? '—'),

                TextColumn::make('penalty_amount')
                    ->label('Penalty Amount')
                    ->money('RWF')
                    ->sortable()
                    ->weight('semibold')
                    ->color(fn ($record) => $record->is_waived ? 'success' : 'danger'),

                TextColumn::make('penalty_date')
                    ->label('Penalty Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->iconColor('gray'),
                    TextColumn::make('status'),

                TextColumn::make('is_waived')

                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Waived' : 'Active')
                    ->color(fn ($state) => $state ? 'success' : 'danger')
                    ->icon(fn ($state) => $state
                        ? 'heroicon-o-check-badge'
                        : 'heroicon-o-exclamation-circle'
                    ),

                TextColumn::make('waived_date')
                    ->label('Waived On')
                    ->date('M j, Y')
                    ->sortable()
                    ->placeholder('—')
                    ->icon('heroicon-o-calendar')
                    ->iconColor('gray')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('waivedBy.name')
                    ->label('Waived By')
                    ->sortable()
                    ->placeholder('—')
                    ->icon('heroicon-o-user')
                    ->iconColor('gray')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->description(fn ($record) => $record->created_at?->diffForHumans())
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Last Updated')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_waived')
                    ->label('Status')
                    ->placeholder('All penalties')
                    ->trueLabel('Waived only')
                    ->falseLabel('Active only'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->iconButton()
                    ->tooltip('View penalty'),

                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit penalty'),

                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete penalty'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('penalty_date', 'desc')
            ->striped()
            ->paginated([10, 25, 50])
            ->emptyStateIcon('heroicon-o-exclamation-circle')
            ->emptyStateHeading('No penalties yet')
            ->emptyStateDescription('Penalties will appear here once recorded.');
    }
}
