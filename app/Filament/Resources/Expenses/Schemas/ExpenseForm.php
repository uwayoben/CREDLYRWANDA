<?php

namespace App\Filament\Resources\Expenses\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class ExpenseForm
{
    public static function configure(Schema $schema): Schema
    {
        $user         = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;

        return $schema
            ->columns(1)
            ->components([
                // Auto-inject company_id and created_by silently
                Hidden::make('company_id')
                    ->default(fn () => $user?->company_id)
                    ->dehydrated(true),

                Hidden::make('created_by')
                    ->default(fn () => $user?->id)
                    ->dehydrated(true),

                Section::make('Expense Details')
                    ->description('Core information about this expense')
                    ->icon('heroicon-o-banknotes')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('expense_number')
                                    ->label('Expense Number')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-hashtag')
                                    ->placeholder('EXP-2024-001')
                                    ->default(fn () => 'EXP-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT)),

                                DatePicker::make('expense_date')
                                    ->label('Expense Date')
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
                                TextInput::make('item')
                                    ->label('Expense Item')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-shopping-bag')
                                    ->placeholder('e.g., Office supplies'),

                                Select::make('category')
                                    ->label('Category')
                                    ->required()
                                    ->prefixIcon('heroicon-o-tag')
                                    ->native(false)
                                    ->searchable()
                                    ->options([
                                        'office_supplies'  => 'Office Supplies',
                                        'utilities'        => 'Utilities',
                                        'salaries'         => 'Salaries',
                                        'rent'             => 'Rent',
                                        'transport'        => 'Transport',
                                        'marketing'        => 'Marketing',
                                        'maintenance'      => 'Maintenance',
                                        'it_equipment'     => 'IT & Equipment',
                                        'training'         => 'Training',
                                        'miscellaneous'    => 'Miscellaneous',
                                    ]),
                            ]),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Brief description of the expense...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Payment Information')
                    ->description('How and when this expense was paid')
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

                                FileUpload::make('receipt_attachment')
                                    ->label('Receipt / Attachment')
                                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                                    ->maxSize(2048)
                                    ->directory('expense-receipts')
                                    ->visibility('private')
                                    ->downloadable()
                                    ->openable()
                                    ->helperText('Upload receipt image or PDF. Max 2MB.'),
                            ]),

                        Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(3)
                            ->maxLength(1000)
                            ->placeholder('Any additional notes or remarks...')
                            ->columnSpanFull(),
                    ]),

                Section::make('Recurring Settings')
                    ->description('Configure if this is a recurring expense')
                    ->icon('heroicon-o-arrow-path')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Toggle::make('is_recurring')
                                    ->label('Recurring Expense')
                                    ->helperText('Enable if this expense repeats on a schedule')
                                    ->onColor('warning')
                                    ->offColor('gray')
                                    ->inline(false)
                                    ->live(),

                                Select::make('recurring_frequency')
                                    ->label('Recurring Frequency')
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-clock')
                                    ->options([
                                        'daily'     => 'Daily',
                                        'weekly'    => 'Weekly',
                                        'monthly'   => 'Monthly',
                                        'quarterly' => 'Quarterly',
                                        'yearly'    => 'Yearly',
                                    ])
                                    ->visible(fn ($get) => $get('is_recurring'))
                                    ->required(fn ($get) => $get('is_recurring')),
                            ]),
                    ]),

                // Super admin only: reassign to a different company
                Section::make('Company Assignment')
                    ->description('Assign this expense to a specific company')
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
