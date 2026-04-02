<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Payment;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TransactionResource extends Resource
{
    // ── Use Payment as the base model ─────────────────────────────────────────
    // Payments + disbursements are unioned at the query level below.
    protected static ?string $model = Payment::class;

    protected static ?string $navigationLabel                = 'Transactions';
    protected static ?string $modelLabel                     = 'Transaction';
    protected static ?string $pluralModelLabel               = 'Transactions';
    protected static ?int    $navigationSort                 = 3;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    // ── Union query: payments + loan disbursements ────────────────────────────

    public static function getEloquentQuery(): Builder
    {
        $user         = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;
        $companyId    = $user?->company_id;

        // ── Payments query ────────────────────────────────────────────────────
        $payments = DB::table('payments as p')
            ->leftJoin('loans as l',     'l.id', '=', 'p.loan_id')
            ->leftJoin('customers as c', 'c.id', '=', 'p.customer_id')
            ->select([
                'p.id',
                DB::raw("'payment' as transaction_type"),
                'p.company_id',
                'p.loan_id',
                'l.loan_number          as loan_ref',
                'p.customer_id',
                'c.names                as customer_name',
                'c.national_id',
                'p.amount',
                'p.principal_paid',
                'p.interest_paid',
                'p.penalty_paid',
                'p.payment_method',
                'p.receipt_number       as reference',
                'p.payment_date         as transaction_date',
                'l.loan_status',
                'l.principal_amount     as original_loan',
                'l.remaining_balance',
                'p.notes',
                'p.created_at',
            ])
            ->when(! $isSuperAdmin, fn ($q) => $q->where('p.company_id', $companyId));

        // ── Disbursements query ───────────────────────────────────────────────
        $disbursements = DB::table('loans as l')
            ->leftJoin('customers as c', 'c.id', '=', 'l.customer_id')
            ->select([
                'l.id',
                DB::raw("'disbursement' as transaction_type"),
                'l.company_id',
                'l.id                   as loan_id',
                'l.loan_number          as loan_ref',
                'l.customer_id',
                'c.names                as customer_name',
                'c.national_id',
                'l.principal_amount     as amount',
                DB::raw('0              as principal_paid'),
                DB::raw('0              as interest_paid'),
                DB::raw('0              as penalty_paid'),
                DB::raw("'disbursement' as payment_method"),
                'l.loan_number          as reference',
                DB::raw('COALESCE(l.disbursement_date, l.approved_at, l.created_at) as transaction_date'),
                'l.loan_status',
                'l.principal_amount     as original_loan',
                'l.remaining_balance',
                'l.notes',
                'l.created_at',
            ])
            ->whereNotIn('l.loan_status', ['pending', 'rejected'])
            ->when(! $isSuperAdmin, fn ($q) => $q->where('l.company_id', $companyId));

        // ── Union both into one query ─────────────────────────────────────────
        $union = $payments->unionAll($disbursements);

        // Wrap in a subquery so Filament can paginate, sort and filter on top
        return Payment::from(DB::raw("({$union->toSql()}) as payments"))
            ->mergeBindings($union)
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');
    }

    // ── Table definition ──────────────────────────────────────────────────────

    public static function table(Table $table): Table
    {
        $isSuperAdmin = Auth::user()?->is_super_admin ?? false;

        return $table
            ->columns([
                TextColumn::make('transaction_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'payment'      => 'success',
                        'disbursement' => 'danger',
                        default        => 'gray',
                    })
                    ->icon(fn ($state) => match ($state) {
                        'payment'      => 'heroicon-o-arrow-up-circle',
                        'disbursement' => 'heroicon-o-arrow-down-circle',
                        default        => 'heroicon-o-question-mark-circle',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'payment'      => 'Payment',
                        'disbursement' => 'Disbursement',
                        default        => ucfirst($state ?? '—'),
                    }),

                TextColumn::make('transaction_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->iconColor('gray'),

                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->icon('heroicon-o-user')
                    ->iconColor('gray')
                    ->description(fn ($record) => $record->national_id
                        ? 'ID: ' . $record->national_id
                        : '—'
                    ),

                TextColumn::make('loan_ref')
                    ->label('Loan')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Copied')
                    ->icon('heroicon-o-document-text')
                    ->iconColor('gray'),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->weight('semibold')
                    ->color(fn ($record) => $record->transaction_type === 'disbursement'
                        ? 'danger'
                        : 'success'
                    )
                    ->formatStateUsing(fn ($record) => $record->transaction_type === 'disbursement'
                        ? '- RWF ' . number_format($record->amount, 0)
                        : '+ RWF ' . number_format($record->amount, 0)
                    )
                    ->description(fn ($record) => $record->transaction_type === 'payment'
                        ? 'Principal: RWF ' . number_format($record->principal_paid, 0) .
                          ' | Interest: RWF ' . number_format($record->interest_paid, 0)
                        : 'Loan disbursement'
                    ),

                TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'cash'          => 'success',
                        'bank_transfer' => 'info',
                        'mobile_money'  => 'warning',
                        'cheque'        => 'gray',
                        'disbursement'  => 'danger',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'bank_transfer' => 'Bank Transfer',
                        'mobile_money'  => 'Mobile Money',
                        'disbursement'  => 'Disbursement',
                        default         => ucfirst($state ?? '—'),
                    }),

                TextColumn::make('reference')
                    ->label('Reference')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Copied')
                    ->icon('heroicon-o-hashtag')
                    ->iconColor('gray'),

                TextColumn::make('loan_status')
                    ->label('Loan Status')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending'     => 'gray',
                        'approved'    => 'info',
                        'disbursed'   => 'warning',
                        'active'      => 'success',
                        'completed'   => 'success',
                        'defaulted'   => 'danger',
                        'written_off' => 'danger',
                        'rejected'    => 'danger',
                        default       => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state ?? '—'))),

                TextColumn::make('original_loan')
                    ->label('Loan Principal')
                    ->money('RWF')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Recorded At')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('transaction_type')
                    ->label('Type')
                    ->options([
                        'payment'      => 'Payments',
                        'disbursement' => 'Disbursements',
                    ]),

                SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash'          => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'mobile_money'  => 'Mobile Money',
                        'cheque'        => 'Cheque',
                    ]),

                SelectFilter::make('loan_status')
                    ->label('Loan Status')
                    ->options([
                        'disbursed'   => 'Disbursed',
                        'active'      => 'Active',
                        'completed'   => 'Completed',
                        'defaulted'   => 'Defaulted',
                        'written_off' => 'Written Off',
                    ]),

                Filter::make('transaction_date')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from_date')->label('From'),
                        \Filament\Forms\Components\DatePicker::make('to_date')->label('To'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from_date'], fn ($q, $d) => $q->whereDate('transaction_date', '>=', $d))
                        ->when($data['to_date'],   fn ($q, $d) => $q->whereDate('transaction_date', '<=', $d))
                    ),
            ])
            ->striped()
            ->paginated([10, 25, 50])
            ->poll('30s')
            ->emptyStateIcon('heroicon-o-arrows-right-left')
            ->emptyStateHeading('No transactions yet')
            ->emptyStateDescription('Loan disbursements and payments will appear here automatically.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
        ];
    }
}