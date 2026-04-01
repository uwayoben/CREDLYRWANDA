<?php

namespace App\Filament\Resources\Companies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CompaniesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn () => 'https://ui-avatars.com/api/?name=Co&background=6366f1&color=fff')
                    ->size(40),

                TextColumn::make('name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record) => $record->registration_number ?? '—')
                    ->icon('heroicon-o-building-office'),

                TextColumn::make('email')
                    ->label('Contact')
                    ->searchable()
                    ->description(fn ($record) => $record->phone ?? '—')
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->copyMessage('Email copied'),

                TextColumn::make('address')
                    ->label('Address')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->address)
                    ->description(fn ($record) => collect([
                        $record->province,
                        $record->district,
                        $record->sector,
                    ])->filter()->implode(', ') ?: '—')
                    ->icon('heroicon-o-map-pin'),

                IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('created_at')
                    ->label('Registered')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
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
                    ->placeholder('All companies')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),

                SelectFilter::make('province')
                    ->label('Province')
                    ->options(fn () => \App\Models\Company::query()
                        ->whereNotNull('province')
                        ->distinct()
                        ->pluck('province', 'province')
                        ->toArray()
                    )
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit company'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50]);
    }
}
