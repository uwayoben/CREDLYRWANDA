<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $user         = Auth::user();
                $isSuperAdmin = $user?->is_super_admin ?? false;

                if ($isSuperAdmin) {
                    // Super admin sees all users from all companies
                    return $query;
                }

                // Regular users see only their company's users
                // AND never see super admin accounts
                return $query
                    ->where('company_id', $user->company_id)
                    ->where('is_super_admin', false);
            })
            ->columns([
                TextColumn::make('name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn ($record) => $record->position ?? '—')
                    ->icon('heroicon-o-user-circle'),

                TextColumn::make('email')
                    ->label('Contact')
                    ->searchable()
                    ->description(fn ($record) => $record->phone ?? '—')
                    ->icon('heroicon-o-envelope')
                    ->copyable()
                    ->copyMessage('Email copied'),

                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-o-building-office')
                    ->placeholder('—')
                    ->visible(fn () => Auth::user()?->is_super_admin ?? false),

                IconColumn::make('is_super_admin')
                    ->label('Super Admin')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-shield-exclamation')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->visible(fn () => Auth::user()?->is_super_admin ?? false),

                TextColumn::make('created_at')
                    ->label('Joined')
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
                TernaryFilter::make('is_super_admin')
                    ->label('Role')
                    ->placeholder('All users')
                    ->trueLabel('Super admins only')
                    ->falseLabel('Regular users only')
                    ->visible(fn () => Auth::user()?->is_super_admin ?? false),

                SelectFilter::make('company')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => Auth::user()?->is_super_admin ?? false),
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit user'),
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