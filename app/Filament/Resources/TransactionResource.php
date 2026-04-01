<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\TransactionView;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TransactionResource extends Resource
{
    protected static ?string $model                          = TransactionView::class;
    protected static ?string $navigationLabel                = 'Transactions';
    protected static ?string $modelLabel                     = 'Transaction';
    protected static ?string $pluralModelLabel               = 'Transactions';
    protected static ?int    $navigationSort                 = 3;
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrows-right-left';

    public static function getEloquentQuery(): Builder
    {
        $user         = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;
        $companyId    = $user?->company_id;

        return TransactionView::query()
            ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('transaction_date', 'desc')
            ->orderBy('id', 'desc');
    }

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
                        'pending'     => 'Pending',
                        'approved'    => 'Approved',
                        'disbursed'   => 'Disbursed',
                        'active'      => 'Active',
                        'completed'   => 'Completed',
                        'defaulted'   => 'Defaulted',
                        'written_off' => 'Written Off',
                        'rejected'    => 'Rejected',
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
