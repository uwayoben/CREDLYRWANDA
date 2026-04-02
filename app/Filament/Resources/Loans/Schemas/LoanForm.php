<?php

namespace App\Filament\Resources\Loans\Schemas;

use App\Models\Customer;
use App\Models\Loan;
use Carbon\Carbon;
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

class LoanForm
{
    public static function configure(Schema $schema): Schema
    {
        $user         = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;

        return $schema
            ->columns(1)
            ->components([
                Hidden::make('company_id')
                    ->default(fn () => $user?->company_id)
                    ->dehydrated(true),

                Hidden::make('created_by')
                    ->default(fn () => $user?->id)
                    ->dehydrated(true),

                // loan_class hidden — always defaults to 'normal' on creation
                // Can be updated later based on arrears tracking
                Hidden::make('loan_class')
                    ->default('normal')
                    ->dehydrated(true),

                // ── Loan Identity ─────────────────────────────────────────────
                Section::make('Loan Identity')
                    ->description('Basic identification details for this loan')
                    ->icon('heroicon-o-document-text')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('loan_number')
                                    ->label('Loan Number')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-hashtag')
                                    ->default(fn () => self::generateLoanNumber($user))
                                    ->helperText('Auto-generated: company prefix + date + sequence'),

                                Select::make('customer_id')
                                    ->label('Customer (Search by National ID or Name)')
                                    ->required()
                                    ->searchable()
                                    ->preload(false)
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-identification')
                                    ->getSearchResultsUsing(function (string $search) use ($isSuperAdmin, $user) {
                                        return Customer::query()
                                            ->when(
                                                ! $isSuperAdmin,
                                                fn ($q) => $q->where('company_id', $user?->company_id)
                                            )
                                            ->where(function ($q) use ($search) {
                                                $q->where('national_id', 'like', "%{$search}%")
                                                  ->orWhere('names', 'like', "%{$search}%");
                                            })
                                            ->limit(20)
                                            ->get()
                                            ->mapWithKeys(fn ($c) => [
                                                $c->id => "{$c->national_id} — {$c->names}",
                                            ]);
                                    })
                                    ->getOptionLabelUsing(function ($value) {
                                        $c = Customer::find($value);
                                        return $c ? "{$c->national_id} — {$c->names}" : $value;
                                    })
                                    ->live()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if (! $state) {
                                            $set('_customer_names',             null);
                                            $set('_customer_national_id',       null);
                                            $set('_customer_phone',             null);
                                            $set('_customer_email',             null);
                                            $set('_customer_dob',               null);
                                            $set('_customer_gender',            null);
                                            $set('_customer_address',           null);
                                            $set('_customer_employer',          null);
                                            $set('_customer_employment_status', null);
                                            $set('_customer_marital_status',    null);
                                            $set('_customer_spouse_name',       null);
                                            $set('_customer_spouse_phone',      null);
                                            return;
                                        }

                                        $customer = Customer::find($state);
                                        if (! $customer) return;

                                        $address = collect([
                                            $customer->village,
                                            $customer->cell,
                                            $customer->sector,
                                            $customer->district,
                                            $customer->province,
                                        ])->filter()->implode(', ');

                                        $set('_customer_names',             $customer->names);
                                        $set('_customer_national_id',       $customer->national_id);
                                        $set('_customer_phone',             $customer->phone);
                                        $set('_customer_email',             $customer->email);
                                        $set('_customer_dob',               $customer->date_of_birth?->format('d / m / Y'));
                                        $set('_customer_gender',            ucfirst($customer->gender ?? ''));
                                        $set('_customer_address',           $address ?: '—');
                                        $set('_customer_employer',          $customer->employer_name);
                                        $set('_customer_employment_status', ucfirst(str_replace('_', ' ', $customer->employment_status ?? '')));
                                        $set('_customer_marital_status',    ucfirst($customer->marital_status ?? ''));
                                        $set('_customer_spouse_name',       $customer->spouse_name);
                                        $set('_customer_spouse_phone',      $customer->spouse_phone);
                                    }),
                            ]),

                        Grid::make(1)
                            ->schema([
                                Select::make('loan_status')
                                    ->label('Loan Status')
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-flag')
                                    ->default('pending')
                                    ->options([
                                        'pending'     => 'Pending',
                                        'approved'    => 'Approved',
                                        'disbursed'   => 'Disbursed',
                                        'active'      => 'Active',
                                        'completed'   => 'Completed',
                                        'defaulted'   => 'Defaulted',
                                        'written_off' => 'Written Off',
                                        'rejected'    => 'Rejected',
                                    ]),
                            ]),
                    ]),

                // ── Customer Information (auto-filled, read-only) ─────────────
                Section::make('Customer Information')
                    ->description('Auto-filled when a customer is selected above')
                    ->icon('heroicon-o-user-circle')
                    ->collapsible()
                    ->collapsed(false)
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('_customer_names')
                                    ->label('Full Names')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-user'),

                                TextInput::make('_customer_national_id')
                                    ->label('National ID')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-identification'),

                                TextInput::make('_customer_phone')
                                    ->label('Phone')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-phone'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('_customer_email')
                                    ->label('Email')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-envelope'),

                                TextInput::make('_customer_dob')
                                    ->label('Date of Birth')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-cake'),

                                TextInput::make('_customer_gender')
                                    ->label('Gender')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-user'),
                            ]),

                        Grid::make(2)
                            ->schema([
                                TextInput::make('_customer_address')
                                    ->label('Address (Village → Province)')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-map-pin'),

                                TextInput::make('_customer_employer')
                                    ->label('Employer')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-building-office-2'),
                            ]),

                        Grid::make(4)
                            ->schema([
                                TextInput::make('_customer_employment_status')
                                    ->label('Employment Status')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-briefcase'),

                                TextInput::make('_customer_marital_status')
                                    ->label('Marital Status')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-heart'),

                                TextInput::make('_customer_spouse_name')
                                    ->label('Spouse Name')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-user'),

                                TextInput::make('_customer_spouse_phone')
                                    ->label('Spouse Phone')
                                    ->disabled()
                                    ->dehydrated(false)
                                    ->prefixIcon('heroicon-o-phone'),
                            ]),
                    ]),

                // ── Loan Terms ────────────────────────────────────────────────
                Section::make('Loan Terms')
                    ->description('Principal, interest and repayment details — totals are calculated automatically')
                    ->icon('heroicon-o-banknotes')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('principal_amount')
                                    ->label('Principal Amount (RWF)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->placeholder('0.00')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                        self::recalculate($set, $get)
                                    ),

                                TextInput::make('interest_rate')
                                    ->label('Interest Rate (% / month)')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->suffix('%')
                                    ->prefixIcon('heroicon-o-percent-badge')
                                    ->placeholder('e.g., 5')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                        self::recalculate($set, $get)
                                    ),

                                Select::make('interest_type')
                                    ->label('Interest Type')
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-calculator')
                                    ->default('declining')
                                    ->options([
                                        'declining' => 'Declining Balance',
                                        'flat'      => 'Flat Rate',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                        self::recalculate($set, $get)
                                    ),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('number_of_installments')
                                    ->label('No. of Installments')
                                    ->required()
                                    ->numeric()
                                    ->minValue(1)
                                    ->prefixIcon('heroicon-o-queue-list')
                                    ->placeholder('e.g., 12')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                        self::recalculate($set, $get)
                                    ),

                                Select::make('installment_frequency')
                                    ->label('Installment Frequency')
                                    ->required()
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-clock')
                                    ->options([
                                        'daily'     => 'Daily',
                                        'weekly'    => 'Weekly',
                                        'bi_weekly' => 'Bi-Weekly',
                                        'monthly'   => 'Monthly',
                                        'quarterly' => 'Quarterly',
                                    ])
                                    ->live()
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                        self::recalculate($set, $get)
                                    ),

                                TextInput::make('penalty_rate')
                                    ->label('Penalty Rate (%)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->suffix('%')
                                    ->prefixIcon('heroicon-o-exclamation-triangle')
                                    ->placeholder('e.g., 2.5'),
                            ]),

                        // Auto-calculated — read only
                        Grid::make(3)
                            ->schema([
                                TextInput::make('emi_amount')
                                    ->label('EMI (Installment Amount) (RWF)')
                                    ->numeric()
                                    ->prefixIcon('heroicon-o-banknotes')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(0)
                                    ->helperText('Auto-calculated per installment.'),

                                TextInput::make('total_interest')
                                    ->label('Total Interest (RWF)')
                                    ->numeric()
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(0),

                                TextInput::make('total_amount')
                                    ->label('Total Repayment (RWF)')
                                    ->numeric()
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(0),
                            ]),
                    ]),

                // ── Repayment Schedule ────────────────────────────────────────
                Section::make('Repayment Schedule')
                    ->description('Disbursement date will be recorded upon loan approval. Enter the first payment date to auto-calculate the expected completion date.')
                    ->icon('heroicon-o-calendar-days')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                DatePicker::make('first_payment_date')
                                    ->label('First Payment Date')
                                    ->required()
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                        self::recalculateDates($set, $get)
                                    )
                                    ->helperText('The date the borrower makes their first repayment.'),

                                DatePicker::make('expected_completion_date')
                                    ->label('Expected Completion Date')
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->helperText('Auto-calculated from first payment date, frequency and number of installments.'),
                            ]),
                    ]),

                // ── Collateral & Guarantee ────────────────────────────────────
                Section::make('Collateral & Guarantee')
                    ->description('Security and guarantee details for this loan')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('collateral_value')
                                    ->label('Collateral Value (RWF)')
                                    ->numeric()
                                    ->prefixIcon('heroicon-o-currency-dollar'),

                                Select::make('guarantee_collateral')
                                    ->label('Guarantee / Collateral Type')
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-shield-check')
                                    ->searchable()
                                    ->options([
                                        'cash_collateral'                                        => 'Cash Collateral',
                                        'government_or_central_bank'                             => 'Government or the Central Bank',
                                        'other_securities_offered_by_banks_operating_in_rwanda' => 'Other Securities Offered by the Banks Operating in Rwanda',
                                        'land_and_building'                                      => 'Land and Building',
                                        'movable_collaterals'                                    => 'Movable Collaterals',
                                    ]),
                            ]),

                        Textarea::make('collateral_details')
                            ->label('Collateral Details')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                // ── Purpose & Notes ───────────────────────────────────────────
                Section::make('Purpose & Notes')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->collapsible()
                    ->schema([
                        Textarea::make('purpose')
                            ->label('Loan Purpose')
                            ->rows(3)
                            ->columnSpanFull(),

                        Textarea::make('notes')
                            ->label('Additional Notes')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                // ── Loan Document ─────────────────────────────────────────────
                Section::make('Loan Document')
                    ->description('Upload the signed loan agreement or any supporting PDF document')
                    ->icon('heroicon-o-paper-clip')
                    ->collapsible()
                    ->schema([
                        FileUpload::make('loan_document')
                            ->label('Loan Document (PDF)')
                            ->disk('public')
                            ->directory('loan-documents')
                            ->acceptedFileTypes(['application/pdf'])
                            ->maxSize(10240)
                            ->downloadable()
                            ->openable()
                            ->previewable(false)
                            ->uploadingMessage('Uploading loan document…')
                            ->helperText('Only PDF files are accepted. Max size: 10 MB.')
                            ->columnSpanFull(),
                    ]),

                // ── Company Assignment (super admin only) ─────────────────────
                Section::make('Company Assignment')
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

    // ── Loan number generator ─────────────────────────────────────────────────

    /**
     * Generate loan number: {2-letter company prefix}{YYYYMMDD}{4-digit sequence}
     * Example: CR20250402-0001
     */
    private static function generateLoanNumber($user): string
    {
        // Get 2-letter company prefix from company name
        $companyName = $user?->company?->name ?? $user?->company_id ?? 'LN';

        // Take first 2 letters, uppercase, strip non-alpha
        $prefix = strtoupper(
            substr(
                preg_replace('/[^A-Za-z]/', '', $companyName),
                0,
                2
            )
        );

        // Fallback if company name has no letters
        if (strlen($prefix) < 2) {
            $prefix = str_pad($prefix, 2, 'X');
        }

        $date = now()->format('Ymd');

        // Count loans created today for this company to get sequence
        $todayCount = Loan::where('company_id', $user?->company_id)
            ->whereDate('created_at', today())
            ->count();

        $sequence = str_pad($todayCount + 1, 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$date}-{$sequence}";
    }

    // ── Auto-calculation helpers ──────────────────────────────────────────────

    private static function recalculate(callable $set, callable $get): void
    {
        $principal = (float) $get('principal_amount');
        $rate      = (float) $get('interest_rate') / 100;
        $n         = (int)   $get('number_of_installments');
        $type      = $get('interest_type') ?? 'declining';

        if ($principal <= 0 || $n <= 0) return;

        if ($type === 'flat') {
            $totalInterest = $principal * $rate * $n;
            $emi           = round(($principal + $totalInterest) / $n, 2);
        } else {
            if ($rate == 0) {
                $totalInterest = 0;
                $emi           = round($principal / $n, 2);
            } else {
                $emi           = ($principal * $rate * pow(1 + $rate, $n)) / (pow(1 + $rate, $n) - 1);
                $totalInterest = ($emi * $n) - $principal;
                $emi           = round($emi, 2);
            }
        }

        $set('emi_amount',     $emi);
        $set('total_interest', round($totalInterest, 2));
        $set('total_amount',   round($principal + $totalInterest, 2));

        self::recalculateDates($set, $get);
    }

    private static function recalculateDates(callable $set, callable $get): void
    {
        $firstPayment = $get('first_payment_date');
        $n            = (int) $get('number_of_installments');
        $frequency    = $get('installment_frequency') ?? 'monthly';

        if ($firstPayment && $n > 0) {
            try {
                $start = Carbon::parse($firstPayment);
                $last  = match ($frequency) {
                    'daily'     => $start->copy()->addDays($n - 1),
                    'weekly'    => $start->copy()->addWeeks($n - 1),
                    'bi_weekly' => $start->copy()->addWeeks(($n - 1) * 2),
                    'quarterly' => $start->copy()->addMonths(($n - 1) * 3),
                    default     => $start->copy()->addMonths($n - 1),
                };
                $set('expected_completion_date', $last->format('Y-m-d'));
            } catch (\Exception $e) {
                // silently ignore
            }
        }
    }
}