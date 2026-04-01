<?php

namespace App\Filament\Resources\Loans\Tables;

use App\Filament\Resources\LoanResource\RelationManagers\PaymentsRelationManager;
use App\Exports\LoansBnrExport;
use App\Exports\LoansCrbExport;
use App\Filament\Exports\LoansStandardExport;
use App\Filament\Exports\BnrReportExport;
use App\Filament\Exports\CrbReportExport;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class LoansTable
{
    public static function configure(Table $table): Table
    {
        $user               = Auth::user();
        $isSuperAdmin       = $user?->is_super_admin ?? false;
        $isManagingDirector = $user?->isManagingDirector() ?? false;

        return $table
            ->modifyQueryUsing(fn (Builder $query) => $isSuperAdmin
                ? $query
                : $query->where('company_id', $user?->company_id)
            )
            ->columns([

                // ── Loan Number ───────────────────────────────────────────────
                TextColumn::make('loan_number')
                    ->label('Loan #')
                    ->searchable()
                    ->sortable()
                    ->size('sm')
                    ->weight('bold')
                    ->copyable()
                    ->copyMessage('Copied!')
                    ->icon('heroicon-m-document-text')
                    ->iconColor('primary'),

                // ── Customer ──────────────────────────────────────────────────
                TextColumn::make('customer.names')
                    ->label('Customer')
                    ->searchable()
                    ->sortable()
                    ->size('sm')
                    ->limit(22)
                    ->tooltip(fn ($record) => $record->customer?->names),

                // ── Loan Class ────────────────────────────────────────────────
                TextColumn::make('loan_class')
                    ->label('Class')
                    ->size('sm')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'normal'       => 'success',
                        'watch'        => 'warning',
                        'substandard'  => 'warning',
                        'doubtful'     => 'danger',
                        'loss'         => 'danger',
                        'restructured' => 'info',
                        'written_off'  => 'gray',
                        default        => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? '—')),

                // ── Status ────────────────────────────────────────────────────
                TextColumn::make('loan_status')
                    ->label('Status')
                    ->size('sm')
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

                // ── Principal Amount ──────────────────────────────────────────
                TextColumn::make('principal_amount')
                    ->label('Principal')
                    ->money('RWF')
                    ->sortable()
                    ->size('sm')
                    ->alignEnd(),

                // ── Amount Paid ───────────────────────────────────────────────
                TextColumn::make('amount_paid')
                    ->label('Paid')
                    ->money('RWF')
                    ->sortable()
                    ->size('sm')
                    ->color('success')
                    ->alignEnd(),

                // ── Principal Paid ────────────────────────────────────────────
                TextColumn::make('principal_paid')
                    ->label('Principal Paid')
                    ->money('RWF')
                    ->sortable()
                    ->size('sm')
                    ->color('success')
                    ->alignEnd()
                    ->toggleable(isToggledHiddenByDefault: false),

                // ── Interest Paid ─────────────────────────────────────────────
                TextColumn::make('interest_paid')
                    ->label('Interest Paid')
                    ->money('RWF')
                    ->sortable()
                    ->size('sm')
                    ->color('info')
                    ->alignEnd(),

                // ── Penalty Paid ──────────────────────────────────────────────
                TextColumn::make('penalty_paid')
                    ->label('Penalty')
                    ->money('RWF')
                    ->sortable()
                    ->size('sm')
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->alignEnd(),

                // ── Remaining Balance ─────────────────────────────────────────
                TextColumn::make('remaining_balance')
                    ->label('Balance')
                    ->getStateUsing(fn ($record) => $record->interest_type === 'declining'
                        ? (float) $record->remaining_balance + ((float) $record->total_interest - (float) $record->interest_paid)
                        : (float) $record->remaining_balance
                    )
                    ->money('RWF')
                    ->sortable()
                    ->size('sm')
                    ->weight('semibold')
                    ->color(fn ($state) => $state > 0 ? 'danger' : 'success')
                    ->alignEnd(),

                // ── Interest Rate ─────────────────────────────────────────────
                TextColumn::make('interest_rate')
                    ->label('Rate')
                    ->size('sm')
                    ->suffix('%')
                    ->sortable()
                    ->alignCenter(),

                // ── Installments paid/total ───────────────────────────────────
                TextColumn::make('number_of_installments')
                    ->label('Inst.')
                    ->size('sm')
                    ->alignCenter()
                    ->formatStateUsing(function ($state, $record) {
                        $paid = $record->installments
                            ? $record->installments->where('status', 'paid')->count()
                            : 0;
                        return $paid . '/' . $state;
                    }),

                // ── Due Date ──────────────────────────────────────────────────
                TextColumn::make('expected_completion_date')
                    ->label('Due')
                    ->date('d/m/Y')
                    ->sortable()
                    ->size('sm')
                    ->color(fn ($record) =>
                        $record->expected_completion_date?->isPast() &&
                        ! in_array($record->loan_status, ['completed', 'written_off'])
                            ? 'danger' : 'gray'
                    ),

                // ── Company (super admin only) ────────────────────────────────
                TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable()
                    ->sortable()
                    ->size('sm')
                    ->limit(16)
                    ->placeholder('—')
                    ->visible($isSuperAdmin)
                    ->toggleable(),

            ])

            // ── Filters ───────────────────────────────────────────────────────
            ->filters([
                SelectFilter::make('loan_status')
                    ->label('Status')
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

                SelectFilter::make('loan_class')
                    ->label('Class')
                    ->options([
                        'normal'       => 'Normal',
                        'watch'        => 'Watch',
                        'substandard'  => 'Substandard',
                        'doubtful'     => 'Doubtful',
                        'loss'         => 'Loss',
                        'restructured' => 'Restructured',
                        'written_off'  => 'Written Off',
                    ]),

                SelectFilter::make('interest_type')
                    ->label('Interest Type')
                    ->options([
                        'declining' => 'Declining Balance',
                        'flat'      => 'Flat Rate',
                    ]),

                SelectFilter::make('installment_frequency')
                    ->label('Frequency')
                    ->options([
                        'daily'     => 'Daily',
                        'weekly'    => 'Weekly',
                        'bi_weekly' => 'Bi-Weekly',
                        'monthly'   => 'Monthly',
                        'quarterly' => 'Quarterly',
                    ]),

                SelectFilter::make('company')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload()
                    ->visible($isSuperAdmin),
            ])

            // ── Header Actions ────────────────────────────────────────────────
            ->headerActions([

                Action::make('export_bnr_full')
                    ->label('BNR Report')
                    ->icon('heroicon-o-document-chart-bar')
                    ->color('warning')
                    ->button()
                    ->form([
                        DatePicker::make('reporting_date')
                            ->label('Reporting Date (Quarter End)')
                            ->required()
                            ->default(now()->endOfQuarter()->format('Y-m-d'))
                            ->prefixIcon('heroicon-o-calendar'),

                        TextInput::make('institution_name')
                            ->label('Institution Name')
                            ->default(fn () => Auth::user()?->company?->name ?? config('app.name'))
                            ->required()
                            ->prefixIcon('heroicon-o-building-office'),

                        TextInput::make('sector')
                            ->label('Sector')
                            ->prefixIcon('heroicon-o-map-pin'),

                        TextInput::make('district')
                            ->label('District')
                            ->prefixIcon('heroicon-o-map-pin'),
                    ])
                    ->modalHeading('Generate BNR Credit Classification Report')
                    ->modalDescription('Produces the official 9-sheet BNR NDFSP report.')
                    ->modalIcon('heroicon-o-document-chart-bar')
                    ->modalSubmitActionLabel('Generate BNR Report')
                    ->action(function (array $data) use ($user, $isSuperAdmin) {
                        $loans = \App\Models\Loan::query()
                            ->with(['customer', 'createdBy', 'installments', 'payments'])
                            ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $user?->company_id))
                            ->get();

                        if ($loans->isEmpty()) {
                            Notification::make()->title('No loans found.')->warning()->send();
                            return;
                        }

                        Notification::make()
                            ->title('BNR report generated — ' . $loans->count() . ' loans')
                            ->success()->send();

                        return Excel::download(
                            new BnrReportExport(
                                $loans,
                                $data['institution_name'],
                                Carbon::parse($data['reporting_date'])->format('d/m/Y'),
                                $data['sector']   ?? '',
                                $data['district'] ?? '',
                            ),
                            'BNR-Report-' . now()->format('Ymd-His') . '.xlsx'
                        );
                    }),

                Action::make('export_crb_full')
                    ->label('CRB Report')
                    ->icon('heroicon-o-shield-check')
                    ->color('info')
                    ->button()
                    ->form([
                        DatePicker::make('reporting_date')
                            ->label('Reporting Date')
                            ->required()
                            ->default(now()->endOfMonth()->format('Y-m-d'))
                            ->prefixIcon('heroicon-o-calendar'),

                        TextInput::make('institution_name')
                            ->label('Institution Name')
                            ->default(fn () => Auth::user()?->company?->name ?? config('app.name'))
                            ->required()
                            ->prefixIcon('heroicon-o-building-office'),

                        TextInput::make('institution_code')
                            ->label('Institution Code')
                            ->placeholder('e.g. CRBIC2025')
                            ->prefixIcon('heroicon-o-hashtag'),
                    ])
                    ->modalHeading('Generate TransUnion CRB Report')
                    ->modalDescription('Produces the TransUnion CRB individual borrowers submission file.')
                    ->modalIcon('heroicon-o-shield-check')
                    ->modalSubmitActionLabel('Generate CRB Report')
                    ->action(function (array $data) use ($user, $isSuperAdmin) {
                        $loans = \App\Models\Loan::query()
                            ->with(['customer', 'createdBy', 'installments', 'payments'])
                            ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $user?->company_id))
                            ->get();

                        if ($loans->isEmpty()) {
                            Notification::make()->title('No loans found.')->warning()->send();
                            return;
                        }

                        Notification::make()
                            ->title('CRB report generated — ' . $loans->count() . ' loans')
                            ->success()->send();

                        return Excel::download(
                            new CrbReportExport(
                                $loans,
                                $data['institution_name'],
                                Carbon::parse($data['reporting_date'])->format('Ymd'),
                                $data['institution_code'] ?? '',
                            ),
                            'CRB-Report-' . now()->format('Ymd-His') . '.xlsx'
                        );
                    }),

                ActionGroup::make([
                    Action::make('export_standard')
                        ->label('Standard Report (Excel)')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->action(function () use ($user, $isSuperAdmin) {
                            $loans = \App\Models\Loan::query()
                                ->with(['customer', 'createdBy', 'installments', 'payments'])
                                ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $user?->company_id))
                                ->get();

                            if ($loans->isEmpty()) {
                                Notification::make()->title('No loans found.')->warning()->send();
                                return;
                            }

                            return Excel::download(
                                new LoansStandardExport(
                                    $loans,
                                    Auth::user()?->company?->name ?? config('app.name'),
                                    now()->format('d/m/Y'),
                                ),
                                'Loans-Standard-' . now()->format('Ymd-His') . '.xlsx'
                            );
                        }),
                ])
                ->label('Export')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->button(),
            ])

            // ── Row Actions ───────────────────────────────────────────────────
            ->recordActions([
                Action::make('view_document')
                    ->icon('heroicon-o-paper-clip')
                    ->color('gray')
                    ->tooltip('View document')
                    ->iconButton()
                    ->visible(fn ($record) => ! empty($record->loan_document))
                    ->url(fn ($record) => asset('storage/' . $record->loan_document))
                    ->openUrlInNewTab(),

                Action::make('approve_loan')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->tooltip('Approve loan')
                    ->iconButton()
                    ->visible(fn ($record) => $isManagingDirector && $record->loan_status === 'pending')
                    ->modalHeading('Approve Loan')
                    ->modalDescription(fn ($record) => "Approve {$record->loan_number} for {$record->customer?->names}.")
                    ->modalIcon('heroicon-o-check-badge')
                    ->modalSubmitActionLabel('Approve')
                    ->form([
                        DatePicker::make('approved_at')
                            ->label('Approval Date')
                            ->required()
                            ->default(now())
                            ->prefixIcon('heroicon-o-calendar'),

                        DatePicker::make('disbursed_at')
                            ->label('Disbursement Date')
                            ->required()
                            ->default(now())
                            ->prefixIcon('heroicon-o-calendar'),
                    ])
                    ->action(function ($record, array $data) use ($user) {
                        $record->update([
                            'loan_status'  => 'disbursed',
                            'approved_by'  => $user?->id,
                            'approved_at'  => Carbon::parse($data['approved_at']),
                            'disbursed_at' => Carbon::parse($data['disbursed_at']),
                        ]);

                        Notification::make()
                            ->title('Loan Approved')
                            ->body("{$record->loan_number} approved.")
                            ->success()->send();
                    }),

                Action::make('make_payment')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->tooltip('Record payment')
                    ->iconButton()
                    ->visible(fn ($record) => in_array($record->loan_status, ['disbursed', 'active']))
                    ->form(fn ($record) => [
                        TextInput::make('receipt_number')
                            ->label('Receipt Number')
                            ->default('RCP-' . strtoupper(uniqid()))
                            ->required()
                            ->prefixIcon('heroicon-o-hashtag'),

                        DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->required()
                            ->default(now())
                            ->prefixIcon('heroicon-o-calendar'),

                        TextInput::make('amount')
                            ->label(function () use ($record) {
                                $trueOutstanding = $record->interest_type === 'declining'
                                    ? (float) $record->remaining_balance + ((float) $record->total_interest - (float) $record->interest_paid)
                                    : (float) $record->remaining_balance;
                                return 'Amount (Remaining: RWF ' . number_format($trueOutstanding, 0) . ')';
                            })
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(function () use ($record) {
                                return $record->interest_type === 'declining'
                                    ? (float) $record->remaining_balance + ((float) $record->total_interest - (float) $record->interest_paid)
                                    : (float) $record->remaining_balance;
                            })
                            ->prefixIcon('heroicon-o-currency-dollar')
                            ->helperText(
                                'Principal paid: RWF ' . number_format($record->principal_paid, 0) .
                                ' | Interest paid: RWF ' . number_format($record->interest_paid, 0)
                            ),

                        Select::make('payment_method')
                            ->label('Payment Method')
                            ->required()
                            ->native(false)
                            ->default('cash')
                            ->prefixIcon('heroicon-o-credit-card')
                            ->options([
                                'cash'          => 'Cash',
                                'bank_transfer' => 'Bank Transfer',
                                'mobile_money'  => 'Mobile Money',
                                'cheque'        => 'Cheque',
                            ]),

                        TextInput::make('transaction_reference')
                            ->label('Transaction Reference')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-document-text')
                            ->placeholder('e.g., MTN-TXN-123456'),

                        Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->recordPayment($data);
                        $fresh         = $record->fresh();
                        $trueRemaining = $fresh->interest_type === 'declining'
                            ? (float) $fresh->remaining_balance + ((float) $fresh->total_interest - (float) $fresh->interest_paid)
                            : (float) $fresh->remaining_balance;

                        Notification::make()
                            ->title('Payment recorded')
                            ->body('RWF ' . number_format($data['amount'], 0) . ' applied. Remaining: RWF ' . number_format($trueRemaining, 0))
                            ->success()->send();
                    }),

                ViewAction::make()->iconButton()->tooltip('View'),
                EditAction::make()->iconButton()->tooltip('Edit'),
                DeleteAction::make()->iconButton()->tooltip('Delete'),
            ])

            // ── Bulk Actions ──────────────────────────────────────────────────
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),

                    \Filament\Actions\BulkAction::make('bulk_export_standard')
                        ->label('Standard Report')
                        ->icon('heroicon-o-table-cells')
                        ->color('success')
                        ->action(function (Collection $records) {
                            $loans = $records->load(['customer', 'createdBy', 'installments', 'payments']);
                            if ($loans->isEmpty()) {
                                Notification::make()->title('No loans selected.')->warning()->send();
                                return;
                            }
                            return Excel::download(
                                new LoansStandardExport($loans),
                                'loans-standard-selected-' . now()->format('Ymd-His') . '.xlsx'
                            );
                        })
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Actions\BulkAction::make('bulk_export_bnr')
                        ->label('BNR Report')
                        ->icon('heroicon-o-document-chart-bar')
                        ->color('warning')
                        ->form([
                            DatePicker::make('reporting_date')
                                ->label('Reporting Date')
                                ->required()
                                ->default(now()->endOfQuarter()->format('Y-m-d'))
                                ->prefixIcon('heroicon-o-calendar'),
                            TextInput::make('institution_name')
                                ->label('Institution Name')
                                ->default(config('app.name'))
                                ->required()
                                ->prefixIcon('heroicon-o-building-office'),
                        ])
                        ->modalHeading('BNR Report for Selected Loans')
                        ->modalSubmitActionLabel('Generate')
                        ->action(function (Collection $records, array $data) {
                            $loans = $records->load(['customer', 'createdBy', 'installments', 'payments']);
                            if ($loans->isEmpty()) {
                                Notification::make()->title('No loans selected.')->warning()->send();
                                return;
                            }
                            return Excel::download(
                                new BnrReportExport(
                                    $loans,
                                    $data['institution_name'],
                                    Carbon::parse($data['reporting_date'])->format('d/m/Y'),
                                ),
                                'bnr-report-selected-' . now()->format('Ymd-His') . '.xlsx'
                            );
                        })
                        ->deselectRecordsAfterCompletion(),

                    \Filament\Actions\BulkAction::make('bulk_export_crb')
                        ->label('CRB Report')
                        ->icon('heroicon-o-shield-check')
                        ->color('info')
                        ->form([
                            DatePicker::make('reporting_date')
                                ->label('Reporting Date')
                                ->required()
                                ->default(now()->endOfMonth()->format('Y-m-d'))
                                ->prefixIcon('heroicon-o-calendar'),
                            TextInput::make('institution_name')
                                ->label('Institution Name')
                                ->default(config('app.name'))
                                ->required()
                                ->prefixIcon('heroicon-o-building-office'),
                        ])
                        ->modalHeading('CRB Report for Selected Loans')
                        ->modalSubmitActionLabel('Generate')
                        ->action(function (Collection $records, array $data) {
                            $loans = $records->load(['customer', 'createdBy', 'installments', 'payments']);
                            if ($loans->isEmpty()) {
                                Notification::make()->title('No loans selected.')->warning()->send();
                                return;
                            }
                            return Excel::download(
                                new CrbReportExport(
                                    $loans,
                                    $data['institution_name'],
                                    Carbon::parse($data['reporting_date'])->format('Ymd'),
                                ),
                                'crb-report-selected-' . now()->format('Ymd-His') . '.xlsx'
                            );
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])

            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([25, 50, 100])
            ->poll('60s')
            ->emptyStateIcon('heroicon-o-document-text')
            ->emptyStateHeading('No loans yet')
            ->emptyStateDescription('Once loans are created, they will appear here.');
    }
}