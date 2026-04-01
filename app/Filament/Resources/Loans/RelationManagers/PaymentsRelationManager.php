<?php

namespace App\Filament\Resources\LoanResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Notifications\Notification;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $recordTitleAttribute = 'receipt_number';

    protected static ?string $title = 'Payments';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('receipt_number')
                    ->required()
                    ->maxLength(255)
                    ->default('RCP-' . strtoupper(uniqid()))
                    ->prefixIcon('heroicon-o-hashtag'),

                Forms\Components\DatePicker::make('payment_date')
                    ->required()
                    ->default(now())
                    ->prefixIcon('heroicon-o-calendar'),

                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->prefixIcon('heroicon-o-currency-dollar'),

                Forms\Components\Select::make('payment_method')
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

                Forms\Components\TextInput::make('transaction_reference')
                    ->maxLength(255)
                    ->prefixIcon('heroicon-o-document-text')
                    ->placeholder('e.g., MTN-TXN-123456'),

                Forms\Components\Textarea::make('notes')
                    ->rows(2)
                    ->placeholder('Optional payment notes...'),

                Forms\Components\FileUpload::make('attachment')
                    ->directory('payments/attachments')
                    ->visibility('private')
                    ->acceptedFileTypes(['application/pdf', 'image/*'])
                    ->maxSize(5120)
                    ->prefixIcon('heroicon-o-paper-clip'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['receivedBy']))
            ->recordTitleAttribute('receipt_number')
            ->columns([
                Tables\Columns\TextColumn::make('receipt_number')
                    ->label('Receipt #')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->icon('heroicon-o-document-text')
                    ->copyable()
                    ->copyMessage('Receipt number copied'),

                Tables\Columns\TextColumn::make('payment_date')
                    ->label('Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->description(fn ($record) => $record->created_at?->diffForHumans())
                    ->icon('heroicon-o-calendar'),

                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('RWF')
                    ->sortable()
                    ->weight('semibold')
                    ->color('success')
                    ->description(fn ($record) => 'Method: ' . ucwords(str_replace('_', ' ', $record->payment_method ?? '—'))),

                Tables\Columns\TextColumn::make('principal_paid')
                    ->label('Principal')
                    ->money('RWF')
                    ->sortable()
                    ->toggleable()
                    ->color('info'),

                Tables\Columns\TextColumn::make('interest_paid')
                    ->label('Interest')
                    ->money('RWF')
                    ->sortable()
                    ->toggleable()
                    ->color('warning'),

                Tables\Columns\TextColumn::make('penalty_paid')
                    ->label('Penalty')
                    ->money('RWF')
                    ->sortable()
                    ->toggleable()
                    ->color('danger'),

                Tables\Columns\TextColumn::make('advance_paid')
                    ->label('Advance')
                    ->money('RWF')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->color('gray'),

                Tables\Columns\TextColumn::make('receivedBy.name')
                    ->label('Received By')
                    ->searchable()
                    ->toggleable()
                    ->icon('heroicon-o-user')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Method')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'cash'          => 'success',
                        'bank_transfer' => 'info',
                        'mobile_money'  => 'warning',
                        'cheque'        => 'gray',
                        default         => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state))),

                Tables\Columns\TextColumn::make('transaction_reference')
                    ->label('Reference')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->icon('heroicon-o-document-text'),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])

            ->filters([
                Tables\Filters\SelectFilter::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'cash'          => 'Cash',
                        'bank_transfer' => 'Bank Transfer',
                        'mobile_money'  => 'Mobile Money',
                        'cheque'        => 'Cheque',
                    ]),

                Tables\Filters\Filter::make('payment_date')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From'),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_date', '<=', $date),
                            );
                    }),

                Tables\Filters\TernaryFilter::make('has_attachment')
                    ->label('Has Attachment')
                    ->placeholder('All payments')
                    ->trueLabel('With attachment')
                    ->falseLabel('Without attachment')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('attachment'),
                        false: fn (Builder $query) => $query->whereNull('attachment'),
                    ),
            ])

            // ── Toolbar (header) actions — Filament v5 uses toolbarActions ────
            ->toolbarActions([
                Action::make('record_payment')
                    ->label('Record Payment')
                    ->icon('heroicon-o-plus')
                    ->color('success')
                    ->modalHeading('Record New Payment')
                    ->modalDescription('Record a payment for this loan')
                    ->modalIcon('heroicon-o-banknotes')
                    ->modalSubmitActionLabel('Save Payment')
                    ->form([
                        Forms\Components\TextInput::make('receipt_number')
                            ->label('Receipt Number')
                            ->required()
                            ->maxLength(255)
                            ->default('RCP-' . strtoupper(uniqid()))
                            ->prefixIcon('heroicon-o-hashtag'),

                        Forms\Components\DatePicker::make('payment_date')
                            ->label('Payment Date')
                            ->required()
                            ->default(now())
                            ->prefixIcon('heroicon-o-calendar'),

                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (RWF)')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->prefixIcon('heroicon-o-currency-dollar'),

                        Forms\Components\Select::make('payment_method')
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

                        Forms\Components\TextInput::make('transaction_reference')
                            ->label('Transaction Reference')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-document-text')
                            ->placeholder('e.g., MTN-TXN-123456'),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->rows(2)
                            ->placeholder('Optional payment notes...'),

                        Forms\Components\FileUpload::make('attachment')
                            ->label('Attachment')
                            ->directory('payments/attachments')
                            ->visibility('private')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(5120),
                    ])
                    ->action(function (array $data, $livewire): void {
                        $loan = $livewire->getOwnerRecord();

                        $data['loan_id']     = $loan->id;
                        $data['customer_id'] = $loan->customer_id;
                        $data['company_id']  = $loan->company_id;
                        $data['received_by'] = auth()->id();

                        $loan->recordPayment($data);

                        $livewire->dispatch('refresh');

                        Notification::make()
                            ->title('Payment recorded successfully')
                            ->success()
                            ->send();
                    }),
            ])

            // ── Row actions — Filament v5 uses recordActions ──────────────────
            ->recordActions([
                ViewAction::make()
                    ->modalHeading('Payment Details')
                    ->modalIcon('heroicon-o-document-text'),

                EditAction::make()
                    ->visible(fn ($record) => auth()->user()->is_super_admin || auth()->user()->isManagingDirector()),

                DeleteAction::make()
                    ->visible(fn ($record) => auth()->user()->is_super_admin)
                    ->requiresConfirmation()
                    ->modalHeading('Delete Payment')
                    ->modalDescription('Are you sure you want to delete this payment? This action cannot be undone and will affect loan calculations.')
                    ->action(function ($record, $livewire): void {
                        $loan   = $livewire->getOwnerRecord();
                        $amount = $record->amount;

                        $loan->decrement('amount_paid', $amount);
                        $loan->decrement('principal_paid', $record->principal_paid);
                        $loan->decrement('interest_paid', $record->interest_paid);
                        $loan->decrement('penalty_paid', $record->penalty_paid);

                        if ($loan->interest_type === 'flat') {
                            $loan->increment('remaining_balance', $record->principal_paid + $record->interest_paid + $record->penalty_paid);
                        } else {
                            $loan->increment('remaining_balance', $record->principal_paid + $record->penalty_paid);
                        }

                        foreach ($record->allocations as $allocation) {
                            $installment = $allocation->installment;
                            if ($installment) {
                                $installment->update(['status' => 'pending']);
                            }
                        }

                        $record->delete();

                        Notification::make()
                            ->title('Payment deleted')
                            ->body('Payment of RWF ' . number_format($amount, 0) . ' has been reversed.')
                            ->warning()
                            ->send();
                    }),
            ])

            // ── Bulk actions ──────────────────────────────────────────────────
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->is_super_admin),
                ]),
            ])

            ->defaultSort('payment_date', 'desc')
            ->paginated([10, 25, 50])
            ->striped()
            ->emptyStateIcon('heroicon-o-banknotes')
            ->emptyStateHeading('No payments recorded')
            ->emptyStateDescription('Record payments to see them here.');
    }

    public static function getTitle(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): string
    {
        $totalPaid = $ownerRecord->amount_paid ?? 0;

        return 'Payments (RWF ' . number_format($totalPaid, 0) . ')';
    }

    public static function canViewForRecord(\Illuminate\Database\Eloquent\Model $ownerRecord, string $pageClass): bool
    {
        return in_array($ownerRecord->loan_status, ['disbursed', 'active', 'completed']);
    }
}