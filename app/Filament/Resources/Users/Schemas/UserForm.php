<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;

        return $schema
            ->columns(1)
            ->components([
                Section::make('Account Information')
                    ->description('Basic credentials and identity for this user')
                    ->icon('heroicon-o-user-circle')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-user')
                                    ->placeholder('John Doe'),

                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->placeholder('john@company.com')
                                    ->unique(ignoreRecord: true),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                                    ->dehydrated(fn ($state) => filled($state))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-lock-closed')
                                    ->placeholder('••••••••')
                                    ->helperText('Leave blank to keep current password when editing'),

                                TextInput::make('password_confirmation')
                                    ->label('Confirm Password')
                                    ->password()
                                    ->revealable()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrated(false)
                                    ->same('password')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-lock-closed')
                                    ->placeholder('••••••••'),
                            ]),
                    ]),

                Section::make('Profile Details')
                    ->description('Additional information about the user')
                    ->icon('heroicon-o-identification')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-phone')
                                    ->placeholder('+250 788 123 456'),

                                Select::make('position')
                                    ->label('Job Position')
                                    ->options([
                                        'Loan Officer' => 'Loan Officer',
                                        'Managing Director' => 'Managing Director',
                                        'Reception' => 'Reception',
                                        'Share Holder' => 'Share Holder',
                                    ])
                                    ->required()
                                    ->prefixIcon('heroicon-o-briefcase')
                                    ->placeholder('Select a position')
                                    ->native(false)
                                    ->searchable(),
                            ]),
                    ]),

                Section::make('Company & Permissions')
                    ->description('Assign company and system access level')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                // Company selection - only visible to super admins
                                Select::make('company_id')
                                    ->label('Company')
                                    ->relationship('company', 'name')
                                    ->required()
                                    ->prefixIcon('heroicon-o-building-office')
                                    ->placeholder('Select a company')
                                    ->native(false)
                                    ->searchable()
                                    ->preload()
                                    ->visible($isSuperAdmin),

                                // Hidden company_id for non-super admins (auto-filled from logged user)
                                Hidden::make('company_id')
                                    ->default($user?->company_id)
                                    ->dehydrated(true)
                                    ->visible(!$isSuperAdmin),

                                // Super Admin toggle - only visible to super admins
                                Toggle::make('is_super_admin')
                                    ->label('Super Admin')
                                    ->helperText('Super admins have full access to all system features')
                                    ->onColor('warning')
                                    ->offColor('gray')
                                    ->inline(false)
                                    ->visible($isSuperAdmin)
                                    ->default(false),

                                // Hidden field for non-super admins to ensure is_super_admin is false
                                Hidden::make('is_super_admin')
                                    ->default(false)
                                    ->dehydrated(true)
                                    ->visible(!$isSuperAdmin),
                            ]),
                    ]),
            ]);
    }
}
