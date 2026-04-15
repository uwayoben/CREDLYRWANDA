<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Loan extends Model
{
    protected $fillable = [
        'company_id', 'customer_id', 'created_by', 'loan_number',
        'principal_amount', 'interest_rate', 'interest_type',
        'number_of_installments', 'installment_frequency',
        'total_interest', 'total_amount', 'processing_fee', 'application_fee',
        'penalty_rate', 'disbursement_date', 'first_payment_date',
        'last_payment_date', 'expected_completion_date', 'loan_status',
        'loan_class', 'date_when_arrears_start', 'arrears_amount',
        'collateral_details', 'collateral_value', 'guarantee_collateral',
        'purpose', 'notes', 'approved_by', 'approved_at',
        'disbursed_at', 'completed_at','managment_fee',
        'amount_paid', 'principal_paid','interest_paid',
        'penalty_paid', 'remaining_balance','loan_document'
    ];

    protected $casts = [
        'disbursement_date'        => 'date',
        'first_payment_date'       => 'date',
        'last_payment_date'        => 'date',
        'expected_completion_date' => 'date',
        'date_when_arrears_start'  => 'date',
        'approved_at'              => 'datetime',
        'disbursed_at'             => 'datetime',
        'completed_at'             => 'datetime',
        'principal_amount'         => 'decimal:2',
        'interest_rate'            => 'decimal:2',
        'total_interest'           => 'decimal:2',
        'total_amount'             => 'decimal:2',
        'processing_fee'           => 'decimal:2',
        'application_fee'          => 'decimal:2',
        'amount_paid'              => 'decimal:2',
        'principal_paid'           => 'decimal:2',
        'interest_paid'            => 'decimal:2',
        'penalty_paid'             => 'decimal:2',
        'remaining_balance'        => 'decimal:2',
    ];

    // ── Relationships ─────────────────────────────────────────────

    public function company()      { return $this->belongsTo(Company::class); }
    public function customer()     { return $this->belongsTo(Customer::class); }
    public function createdBy()    { return $this->belongsTo(User::class, 'created_by'); }
    public function approvedBy()   { return $this->belongsTo(User::class, 'approved_by'); }
    public function installments() { return $this->hasMany(Installment::class); }
    public function payments()     { return $this->hasMany(Payment::class); }
    public function penalties()    { return $this->hasMany(Penality::class); }

    // ── Installment Generation ────────────────────────────────────

    public function generateInstallments(): void
    {
        $this->installments()->delete();

        $principal = (float) $this->principal_amount;
        $rate      = (float) $this->interest_rate / 100;
        $n         = (int)   $this->number_of_installments;
        $type      = $this->interest_type;
        $startDate = Carbon::parse($this->first_payment_date);

        $schedule = $type === 'flat'
            ? $this->buildFlatSchedule($principal, $rate, $n, $startDate)
            : $this->buildDecliningSchedule($principal, $rate, $n, $startDate);

        foreach ($schedule as $row) {
            Installment::create([
                'loan_id'            => $this->id,
                'installment_number' => $row['number'],
                'due_date'           => $row['due_date'],
                'principal_due'      => $row['principal'],
                'interest_due'       => $row['interest'],
                'total_due'          => $row['principal'] + $row['interest'],
                'penalty_due'        => 0,
                'status'             => 'pending',
            ]);
        }

        // ✅ FIX 1: declining tracks principal only, flat tracks total repayment
        $startingBalance = $type === 'flat'
            ? (float) $this->total_amount
            : (float) $this->principal_amount;

        $this->update(['remaining_balance' => $startingBalance]);
    }

    // ── Declining Balance (EMI) ───────────────────────────────────

    private function buildDecliningSchedule(float $P, float $r, int $n, Carbon $start): array
    {
        if ($r == 0) {
            $principal = $P / $n;
            return array_map(fn($i) => [
                'number'    => $i + 1,
                'due_date'  => $start->copy()->addMonths($i),
                'principal' => round($principal, 2),
                'interest'  => 0,
            ], range(0, $n - 1));
        }

        $emi      = ($P * $r * pow(1 + $r, $n)) / (pow(1 + $r, $n) - 1);
        $balance  = $P;
        $schedule = [];

        for ($i = 0; $i < $n; $i++) {
            $interest  = round($balance * $r, 2);
            $principal = round($emi - $interest, 2);

            if ($i === $n - 1) {
                $principal = round($balance, 2);
            }

            $schedule[] = [
                'number'    => $i + 1,
                'due_date'  => $this->getNextDueDate($start, $i),
                'principal' => $principal,
                'interest'  => $interest,
            ];

            $balance -= $principal;
        }

        return $schedule;
    }

    // ── Flat Rate ─────────────────────────────────────────────────

    private function buildFlatSchedule(float $P, float $r, int $n, Carbon $start): array
    {
        $totalInterest = $P * $r * $n;
        $principalPer  = round($P / $n, 2);
        $interestPer   = round($totalInterest / $n, 2);

        return array_map(fn($i) => [
            'number'    => $i + 1,
            'due_date'  => $this->getNextDueDate($start, $i),
            'principal' => $principalPer,
            'interest'  => $interestPer,
        ], range(0, $n - 1));
    }

    // ── Due Date by Frequency ─────────────────────────────────────

    private function getNextDueDate(Carbon $start, int $index): Carbon
    {
        return match ($this->installment_frequency) {
            'daily'     => $start->copy()->addDays($index),
            'weekly'    => $start->copy()->addWeeks($index),
            'bi_weekly' => $start->copy()->addWeeks($index * 2),
            'quarterly' => $start->copy()->addMonths($index * 3),
            default     => $start->copy()->addMonths($index),
        };
    }

    // ── Payment Processing ────────────────────────────────────────

    public function recordPayment(array $data): Payment
    {
        $remaining = (float) $data['amount'];

        $interestPaid  = 0;
        $principalPaid = 0;
        $penaltyPaid   = 0;
        $allocations   = [];

        $installments = $this->installments()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->get();

        foreach ($installments as $installment) {
            if ($remaining <= 0) break;

            $interestOwed  = (float) $installment->interest_due  - $this->getPaidInterestForInstallment($installment->id);
            $principalOwed = (float) $installment->principal_due - $this->getPaidPrincipalForInstallment($installment->id);
            $penaltyOwed   = (float) $installment->penalty_due   - $this->getPaidPenaltyForInstallment($installment->id);

            $allocInterest  = 0;
            $allocPrincipal = 0;
            $allocPenalty   = 0;

            // 1. Pay penalty
            if ($penaltyOwed > 0 && $remaining > 0) {
                $allocPenalty = min($remaining, $penaltyOwed);
                $remaining   -= $allocPenalty;
                $penaltyPaid += $allocPenalty;
            }

            // 2. Pay interest
            if ($interestOwed > 0 && $remaining > 0) {
                $allocInterest  = min($remaining, $interestOwed);
                $remaining     -= $allocInterest;
                $interestPaid  += $allocInterest;
            }

            // 3. Pay principal
            if ($principalOwed > 0 && $remaining > 0) {
                $allocPrincipal = min($remaining, $principalOwed);
                $remaining     -= $allocPrincipal;
                $principalPaid += $allocPrincipal;
            }

            if ($allocInterest + $allocPrincipal + $allocPenalty > 0) {
                $allocations[] = [
                    'installment_id'   => $installment->id,
                    'principal_amount' => $allocPrincipal,
                    'interest_amount'  => $allocInterest,
                    'penalty_amount'   => $allocPenalty,
                ];

                $totalPaid = $allocInterest + $allocPrincipal + $allocPenalty;
                $totalDue  = $interestOwed  + $principalOwed  + $penaltyOwed;

                if (round($totalPaid, 2) >= round($totalDue, 2)) {
                    $installment->update([
                        'status'    => 'paid',
                        'paid_date' => $data['payment_date'],
                    ]);
                } else {
                    $installment->update(['status' => 'partial']);
                }
            }
        }

        // Create Payment record
        $payment = Payment::create([
            'company_id'            => $this->company_id,
            'loan_id'               => $this->id,
            'customer_id'           => $this->customer_id,
            'received_by'           => $data['received_by'] ?? auth()->id(),
            'receipt_number'        => $data['receipt_number'] ?? 'RCP-' . strtoupper(uniqid()),
            'amount'                => $data['amount'],
            'payment_date'          => $data['payment_date'],
            'payment_method'        => $data['payment_method'],
            'principal_paid'        => $principalPaid,
            'interest_paid'         => $interestPaid,
            'penalty_paid'          => $penaltyPaid,
            'advance_paid'          => max(0, $remaining),
            'notes'                 => $data['notes'] ?? null,
            'transaction_reference' => $data['transaction_reference'] ?? null,
        ]);

        // Create payment allocations
        foreach ($allocations as $alloc) {
            PaymentAllocation::create(array_merge(
                $alloc,
                ['payment_id' => $payment->id]
            ));
        }

        // ── Update loan totals ────────────────────────────────────
        $this->increment('amount_paid', $data['amount']);
        $this->increment('principal_paid', $principalPaid);
        $this->increment('interest_paid', $interestPaid);
        $this->increment('penalty_paid', $penaltyPaid);

        // ✅ FIX 2: declining reduces by principal only (interest is a period cost)
        //           flat reduces by full allocated amount
        if ($this->interest_type === 'flat') {
            $this->decrement('remaining_balance', $principalPaid + $interestPaid + $penaltyPaid);
        } else {
            $this->decrement('remaining_balance', $principalPaid + $penaltyPaid);
        }

        // Check if fully paid
        if (round((float) $this->fresh()->remaining_balance, 2) <= 0) {
            $this->update([
                'loan_status'  => 'completed',
                'completed_at' => $data['payment_date'],
            ]);
            $this->installments()
                ->where('status', '!=', 'paid')
                ->update(['status' => 'paid']);
        }

        return $payment;
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function getPaidInterestForInstallment(int $installmentId): float
    {
        return (float) PaymentAllocation::where('installment_id', $installmentId)->sum('interest_amount');
    }

    private function getPaidPrincipalForInstallment(int $installmentId): float
    {
        return (float) PaymentAllocation::where('installment_id', $installmentId)->sum('principal_amount');
    }

    private function getPaidPenaltyForInstallment(int $installmentId): float
    {
        return (float) PaymentAllocation::where('installment_id', $installmentId)->sum('penalty_amount');
    }
}
