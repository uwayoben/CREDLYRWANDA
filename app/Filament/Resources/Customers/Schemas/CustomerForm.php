<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use App\Models\Loan;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;
use Illuminate\Validation\Rule;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();

        return $schema
            ->columns(1)
            ->components([
                // Hidden company_id - auto-filled from logged user
                Hidden::make('company_id')
                    ->default(fn () => $user?->company_id)
                    ->dehydrated(true),

                Section::make('Personal Information')
                    ->description('Basic personal details of the customer')
                    ->icon('heroicon-o-user')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('names')
                                    ->label('Full Names')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-user')
                                    ->placeholder('John Doe'),

                                // ── National ID — auto-fills from any company ──────
                                TextInput::make('national_id')
                                    ->label('National ID')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-identification')
                                    ->placeholder('1234567890123456')
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, callable $set, callable $get, $record) use ($user) {
                                        if (blank($state)) return;

                                        // Skip if editing and ID hasn't changed
                                        if ($record && $record->national_id === $state) return;

                                        // The real national ID is always the first 16 characters
                                        $baseId = substr($state, 0, 16);

                                        // Does this customer already exist in THIS company?
                                        // Check both exact ID and any suffixed version (e.g. 12002801113240010)
                                        $existsInThisCompany = Customer::where('company_id', $user?->company_id)
                                            ->where(function ($q) use ($baseId) {
                                                $q->where('national_id', $baseId)                // exact
                                                  ->orWhere('national_id', 'like', $baseId . '%'); // suffixed
                                            })
                                            ->exists();

                                        if ($existsInThisCompany) {
                                            // Reset the field and let validation show the error
                                            $set('national_id', $state);
                                            return;
                                        }

                                        // Does this ID exist in ANOTHER company?
                                        $existsInOtherCompany = Customer::where('national_id', $baseId)
                                            ->where('company_id', '!=', $user?->company_id)
                                            ->first();

                                        if (! $existsInOtherCompany) return;

                                        // ── Auto-fill from the other company's record ─────────────
                                        $set('names',                   $existsInOtherCompany->names);
                                        $set('date_of_birth',           $existsInOtherCompany->date_of_birth?->format('Y-m-d'));
                                        $set('gender',                  $existsInOtherCompany->gender);
                                        $set('marital_status',          $existsInOtherCompany->marital_status);
                                        $set('email',                   $existsInOtherCompany->email);
                                        $set('phone',                   $existsInOtherCompany->phone);
                                        $set('employment_status',       $existsInOtherCompany->employment_status);
                                        $set('employer_name',           $existsInOtherCompany->employer_name);
                                        $set('province',                $existsInOtherCompany->province);
                                        $set('district',                $existsInOtherCompany->district);
                                        $set('sector',                  $existsInOtherCompany->sector);
                                        $set('cell',                    $existsInOtherCompany->cell);
                                        $set('village',                 $existsInOtherCompany->village);
                                        $set('relationship_with_ndfsp', $existsInOtherCompany->relationship_with_ndfsp);
                                        $set('spouse_name',             $existsInOtherCompany->spouse_name);
                                        $set('spouse_phone',            $existsInOtherCompany->spouse_phone);
                                        $set('spause_id_number',        $existsInOtherCompany->spause_id_number);
                                        $set('marital_property_regime', $existsInOtherCompany->marital_property_regime);
                                        $set('is_active',               $existsInOtherCompany->is_active);

                                        // ── Append 0, 1, 2... to make it unique globally ──────────
                                        $suffix   = 0;
                                        $uniqueId = $baseId . $suffix;

                                        while (Customer::where('national_id', $uniqueId)->exists()) {
                                            $suffix++;
                                            $uniqueId = $baseId . $suffix;
                                        }

                                        $set('national_id', $uniqueId);

                                        // ── Check for unpaid loans in other companies ─────────────
                                        $hasUnpaidLoans = Loan::whereHas('customer', function ($q) use ($baseId) {
                                            $q->where('national_id', $baseId)
                                              ->orWhere('national_id', 'like', $baseId . '%');
                                        })
                                        ->whereNotIn('loan_status', ['completed', 'rejected', 'written_off'])
                                        ->where('company_id', '!=', $user?->company_id)
                                        ->exists();

                                        $set('_loan_warning', $hasUnpaidLoans ? '1' : '0');
                                    })
                                    // Same company duplicate check — covers base ID and all suffixed versions
                                    ->rules([
                                        function ($get, $record) use ($user) {
                                            return function (string $attribute, $value, $fail) use ($user, $record) {
                                                $baseId = substr($value, 0, 16);

                                                $exists = Customer::where('company_id', $user?->company_id)
                                                    ->where(function ($q) use ($baseId) {
                                                        $q->where('national_id', $baseId)
                                                          ->orWhere('national_id', 'like', $baseId . '%');
                                                    })
                                                    ->when($record, fn ($q) => $q->where('id', '!=', $record->id))
                                                    ->exists();

                                                if ($exists) {
                                                    $fail('This customer already exists in your company.');
                                                }
                                            };
                                        },
                                    ]),

                                DatePicker::make('date_of_birth')
                                    ->label('Date of Birth')
                                    ->required()
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->maxDate(now()),
                            ]),

                        // ── Unpaid loan warning ───────────────────────────────
                        Hidden::make('_loan_warning')
                            ->default('0')
                            ->dehydrated(false),

                        Placeholder::make('_loan_warning_display')
                            ->label('')
                            ->content(fn ($get) => $get('_loan_warning') === '1'
                                ? new HtmlString('
                                    <div style="
                                        background: #FEF3C7;
                                        border: 1.5px solid #F59E0B;
                                        border-left: 5px solid #D97706;
                                        border-radius: 8px;
                                        padding: 14px 16px;
                                        display: flex;
                                        align-items: flex-start;
                                        gap: 12px;
                                    ">
                                        <svg xmlns="http://www.w3.org/2000/svg" style="width:22px;height:22px;flex-shrink:0;margin-top:1px;color:#D97706" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
                                        </svg>
                                        <div>
                                            <p style="font-weight:700;color:#92400E;margin:0 0 2px 0;font-size:14px;">
                                                ⚠ Unpaid Loan Detected
                                            </p>
                                            <p style="color:#92400E;margin:0;font-size:13px;">
                                                This customer has one or more unpaid loans in another NSFP institution. Please verify before proceeding.
                                            </p>
                                        </div>
                                    </div>
                                ')
                                : new HtmlString('')
                            )
                            ->visible(fn ($get) => $get('_loan_warning') === '1'),

                        Grid::make(3)
                            ->schema([
                                Select::make('gender')
                                    ->label('Gender')
                                    ->options([
                                        'male'   => 'Male',
                                        'female' => 'Female',
                                        'other'  => 'Other',
                                    ])
                                    ->required()
                                    ->prefixIcon('heroicon-o-user-circle')
                                    ->native(false),

                                Select::make('marital_status')
                                    ->label('Marital Status')
                                    ->options([
                                        'single'   => 'Single',
                                        'married'  => 'Married',
                                        'divorced' => 'Divorced',
                                        'widowed'  => 'Widowed',
                                    ])
                                    ->required()
                                    ->prefixIcon('heroicon-o-heart')
                                    ->native(false)
                                    ->live()
                                    ->afterStateUpdated(fn (callable $set) => $set('spouse_name', null)),

                                TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->placeholder('customer@example.com'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-phone')
                                    ->placeholder('+250 788 123 456'),

                                Select::make('employment_status')
                                    ->label('Employment Status')
                                    ->options([
                                        'employed'      => 'Employed',
                                        'self_employed' => 'Self Employed',
                                        'unemployed'    => 'Unemployed',
                                        'retired'       => 'Retired',
                                    ])
                                    ->nullable()
                                    ->prefixIcon('heroicon-o-briefcase')
                                    ->native(false),

                                TextInput::make('employer_name')
                                    ->label('Employer Name')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-building-office')
                                    ->placeholder('Company name'),
                            ]),
                    ]),

                Section::make('Address Information')
                    ->description('Customer residential address')
                    ->icon('heroicon-o-map-pin')
                    ->collapsible()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('province')
                                    ->label('Province')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-map')
                                    ->placeholder('e.g., Kigali City'),

                                TextInput::make('district')
                                    ->label('District')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-map')
                                    ->placeholder('e.g., Gasabo'),

                                TextInput::make('sector')
                                    ->label('Sector')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-map')
                                    ->placeholder('e.g., Kimironko'),
                            ]),

                        Grid::make(3)
                            ->schema([
                                TextInput::make('cell')
                                    ->label('Cell')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-map')
                                    ->placeholder('e.g., Kibagabaga'),

                                TextInput::make('village')
                                    ->label('Village')
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-map')
                                    ->placeholder('e.g., Amarembo'),

                                TextInput::make('relationship_with_ndfsp')
                                    ->label('Relationship with NDFSP')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-link')
                                    ->placeholder('e.g., Member, Partner'),
                            ]),
                    ]),

                Section::make('Spouse Information')
                    ->description('Details about the spouse (for married customers)')
                    ->icon('heroicon-o-user-group')
                    ->collapsible()
                    ->visible(fn ($get) => $get('marital_status') === 'married')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextInput::make('spouse_name')
                                    ->label('Spouse Full Name')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-user')
                                    ->placeholder('Jane Doe'),

                                TextInput::make('spouse_phone')
                                    ->label('Spouse Phone Number')
                                    ->tel()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-phone')
                                    ->placeholder('+250 788 123 456'),

                                TextInput::make('spause_id_number')
                                    ->label('Spouse National ID')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-identification')
                                    ->placeholder('1234567890123456'),
                            ]),

                        Grid::make(1)
                            ->schema([
                                TextInput::make('marital_property_regime')
                                    ->label('Marital Property Regime')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-scale')
                                    ->placeholder('e.g., Community of property, Separation of property'),
                            ]),
                    ]),

                Section::make('Additional Information')
                    ->description('Other customer details and status')
                    ->icon('heroicon-o-cog')
                    ->collapsible()
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('photo')
                                    ->label('Photo URL')
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-photo')
                                    ->placeholder('path/to/photo.jpg'),

                                Toggle::make('is_active')
                                    ->label('Active Customer')
                                    ->required()
                                    ->default(true)
                                    ->helperText('Inactive customers cannot take new loans')
                                    ->onColor('success')
                                    ->offColor('danger')
                                    ->inline(false),
                            ]),
                    ]),
            ]);
    }
}