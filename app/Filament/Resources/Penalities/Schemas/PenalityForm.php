<?php

namespace App\Filament\Resources\Penalities\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class PenalityForm
{
    public static function configure(Schema $schema): Schema
    {
        $user         = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;

        return $schema
            ->columns(1)
            ->components([
                Section::make('Penalty Details')
                    ->description('Core information about this penalty')
                    ->icon('heroicon-o-exclamation-circle')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('loan_id')
                                    ->label('Loan')
                                    ->relationship(
                                        'loan',
                                        'loan_number',
                                        fn ($query) => $isSuperAdmin
                                            ? $query
                                            : $query->where('company_id', $user?->company_id)
                                    )
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-document-text'),

                                Select::make('installment_id')
                                    ->label('Installment')
                                    ->relationship('installment', 'installment_number')
                                    ->searchable()
                                    ->preload()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-queue-list')
                                    ->placeholder('Select installment (optional)'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('penalty_amount')
                                    ->label('Penalty Amount (RWF)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->placeholder('0.00'),

                                DatePicker::make('penalty_date')
                                    ->label('Penalty Date')
                                    ->required()
                                    ->default(now())
                                    ->prefixIcon('heroicon-o-calendar'),
                            ]),

                        Textarea::make('reason')
                            ->label('Reason')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Reason for this penalty...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Waiver Information')
                    ->description('Mark this penalty as waived if applicable')
                    ->icon('heroicon-o-check-badge')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_waived')
                                    ->label('Waive This Penalty')
                                    ->helperText('Toggle on to mark this penalty as waived')
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false)
                                    ->live(),

                                DatePicker::make('waived_date')
                                    ->label('Waived On')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->default(now())
                                    ->visible(fn ($get) => $get('is_waived')),
                            ]),

                        Select::make('waived_by')
                            ->label('Waived By')
                            ->relationship('waivedBy', 'name')
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->prefixIcon('heroicon-o-user-circle')
                            ->placeholder('Select user who waived')
                            ->default(fn () => $user?->id)
                            ->visible(fn ($get) => $get('is_waived')),
                    ]),
            ]);
    }
}
