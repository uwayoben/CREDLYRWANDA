<?php

namespace App\Filament\Resources\OtherIncomes\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class OtherIncomeForm
{
    public static function configure(Schema $schema): Schema
    {
        $user         = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;

        return $schema
            ->columns(1)
            ->components([
                // Auto-injected silently for all users
                Hidden::make('company_id')
                    ->default(fn () => $user?->company_id)
                    ->dehydrated(true),

                Hidden::make('received_by')
                    ->default(fn () => $user?->id)
                    ->dehydrated(true),

                Section::make('Income Details')
                    ->description('Core information about this income entry')
                    ->icon('heroicon-o-arrow-trending-up')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('income_number')
                                    ->label('Income Number')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-hashtag')
                                    ->placeholder('INC-2024-001')
                                    ->default(fn () => 'INC-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT)),

                                DatePicker::make('income_date')
                                    ->label('Income Date')
                                    ->required()
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->default(now())
                                    ->maxDate(now()),

                                TextInput::make('amount')
                                    ->label('Amount (RWF)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->placeholder('0.00'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('source')
                                    ->label('Income Source')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-building-office')
                                    ->placeholder('e.g., Membership fees, Donations'),

                                TextInput::make('reference_number')
                                    ->label('Reference Number')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-document-text')
                                    ->placeholder('e.g., TXN-789456'),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Brief description of this income...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Payment Information')
                    ->description('Payment method and supporting documents')
                    ->icon('heroicon-o-credit-card')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-credit-card')
                                    ->default('cash')
                                    ->options([
                                        'cash'          => 'Cash',
                                        'bank_transfer' => 'Bank Transfer',
                                        'mobile_money'  => 'Mobile Money',
                                        'cheque'        => 'Cheque',
                                    ]),

                                FileUpload::make('attachment')
                                    ->label('Attachment')
                                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                                    ->maxSize(2048)
                                    ->directory('income-attachments')
                                    ->visibility('private')
                                    ->downloadable()
                                    ->openable()
                                    ->helperText('Upload proof of payment or receipt. Max 2MB.'),
                            ]),

                        Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Any additional notes or remarks...')
                            ->columnSpanFull(),
                    ]),

                // Super admin only: reassign to a different company
                Section::make('Company Assignment')
                    ->description('Assign this income entry to a specific company')
                    ->icon('heroicon-o-building-office')
                    ->collapsible()
                    ->visible($isSuperAdmin)
                    ->schema([
                        Select::make('company_id')
                            ->label('Company')
                            ->relationship('company', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->prefixIcon('heroicon-o-building-office')
                            ->default(fn () => $user?->company_id),
                    ]),
            ]);
    }
}
