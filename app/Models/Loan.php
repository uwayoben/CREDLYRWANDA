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
        'disbursed_at', 'completed_at', 'managment_fee',
        'amount_paid', 'principal_paid', 'interest_paid',
        'penalty_paid', 'remaining_balance', 'loan_document',
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

    // ── Float helper ──────────────────────────────────────────────
    // Prevents floating-point drift (e.g. 69999.9999 instead of 70000)

    private static function money(float $value): float
    {
        return round($value, 2);
    }

    // ── Payment Processing ────────────────────────────────────────

    public function recordPayment(array $data): Payment
    {
        // Round immediately — prevents float drift throughout
        $remaining = self::money((float) $data['amount']);

        $interestPaid  = 0.0;
        $principalPaid = 0.0;
        $penaltyPaid   = 0.0;
        $allocations   = [];

        // ── STEP 1: Pay standalone penalties FIRST ────────────────
        // Penalties live in the penalties table and are NOT part of
        // remaining_balance. They must never reduce remaining_balance.
        //
        // is_waived is NEVER touched here — only manager waive actions
        // set that flag. Payment progress is tracked via:
        //   - penalty.status  → 'unpaid' | 'partial' | 'paid'
        //   - loan.penalty_paid → running total of all penalty payments

        $unpaidPenalties = $this->penalties()
            ->where('is_waived', false)
            ->whereIn('status', ['unpaid', 'partial']) // skip already-paid penalties
            ->get();

        foreach ($unpaidPenalties as $penalty) {
            if ($remaining <= 0) break;

            $penaltyAmount      = self::money((float) $penalty->penalty_amount);
            $paidForThisPenalty = self::money(min($remaining, $penaltyAmount));

            if ($paidForThisPenalty > 0) {
                $penaltyPaid += $paidForThisPenalty;
                $remaining    = self::money($remaining - $paidForThisPenalty);

                $allocations[] = [
                    'penalty_id'       => $penalty->id,
                    'installment_id'   => null,
                    'principal_amount' => 0,
                    'interest_amount'  => 0,
                    'penalty_amount'   => $paidForThisPenalty,
                ];

                // Update penalty status — never touch is_waived here
                $penalty->update([
                    'status' => self::money($penaltyAmount - $paidForThisPenalty) <= 0
                        ? 'paid'     // fully covered → status = paid
                        : 'partial', // partially covered → status = partial
                ]);
            }
        }

        // ── STEP 2: Pay installments (penalty → interest → principal) ─
        // Only $remaining (after penalties above) reduces the balance.

        $installments = $this->installments()
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->get();

        foreach ($installments as $installment) {
            if ($remaining <= 0) break;

            $interestOwed  = self::money(
                (float) $installment->interest_due  - $this->getPaidInterestForInstallment($installment->id)
            );
            $principalOwed = self::money(
                (float) $installment->principal_due - $this->getPaidPrincipalForInstallment($installment->id)
            );
            $penaltyOwed   = self::money(
                (float) $installment->penalty_due   - $this->getPaidPenaltyForInstallment($installment->id)
            );

            $allocPenalty   = 0.0;
            $allocInterest  = 0.0;
            $allocPrincipal = 0.0;

            // 1. Installment-level penalty
            if ($penaltyOwed > 0 && $remaining > 0) {
                $allocPenalty = self::money(min($remaining, $penaltyOwed));
                $remaining    = self::money($remaining - $allocPenalty);
                $penaltyPaid += $allocPenalty;
            }

            // 2. Interest
            if ($interestOwed > 0 && $remaining > 0) {
                $allocInterest  = self::money(min($remaining, $interestOwed));
                $remaining      = self::money($remaining - $allocInterest);
                $interestPaid  += $allocInterest;
            }

            // 3. Principal
            if ($principalOwed > 0 && $remaining > 0) {
                $allocPrincipal = self::money(min($remaining, $principalOwed));
                $remaining      = self::money($remaining - $allocPrincipal);
                $principalPaid += $allocPrincipal;
            }

            if ($allocInterest + $allocPrincipal + $allocPenalty > 0) {
                $allocations[] = [
                    'installment_id'   => $installment->id,
                    'penalty_id'       => null,
                    'principal_amount' => $allocPrincipal,
                    'interest_amount'  => $allocInterest,
                    'penalty_amount'   => $allocPenalty,
                ];

                $totalAllocated = self::money($allocInterest + $allocPrincipal + $allocPenalty);
                $totalDue       = self::money($interestOwed  + $principalOwed  + $penaltyOwed);

                $installment->update(
                    $totalAllocated >= $totalDue
                        ? ['status' => 'paid',    'paid_date' => $data['payment_date']]
                        : ['status' => 'partial']
                );
            }
        }

        // ── STEP 3: Create Payment record ─────────────────────────

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
            'advance_paid'          => self::money(max(0, $remaining)),
            'notes'                 => $data['notes']                 ?? null,
            'transaction_reference' => $data['transaction_reference'] ?? null,
        ]);

        // ── STEP 4: Create payment allocations ────────────────────

        foreach ($allocations as $alloc) {
            PaymentAllocation::create(array_merge($alloc, ['payment_id' => $payment->id]));
        }

        // ── STEP 5: Update loan totals ────────────────────────────

        $this->increment('amount_paid',    $data['amount']);
        $this->increment('principal_paid', $principalPaid);
        $this->increment('interest_paid',  $interestPaid);
        $this->increment('penalty_paid',   $penaltyPaid);

        // remaining_balance tracks principal (+ interest for flat loans).
        // Penalties are a SEPARATE obligation and must NEVER reduce it.

        if ($this->interest_type === 'flat') {
            $this->decrement('remaining_balance', self::money($principalPaid + $interestPaid));
        } else {
            $this->decrement('remaining_balance', $principalPaid);
        }

        // ── STEP 6: Check if loan is fully paid ───────────────────
        // remainingPenalty uses sum(penalty_amount) minus penalty_paid
        // across non-waived penalties only. is_waived is never set by
        // payments so this correctly reflects genuinely unpaid amounts.

        $fresh = $this->fresh();

        $remainingPenalty = self::money(
            (float) $fresh->penalties()->where('is_waived', false)->sum('penalty_amount') -
            (float) $fresh->penalty_paid
        );

        $totalOutstanding = $fresh->interest_type === 'declining'
            ? self::money(
                self::money(max(0, (float) $fresh->remaining_balance)) +
                self::money(max(0, (float) $fresh->total_interest - (float) $fresh->interest_paid)) +
                max(0, $remainingPenalty)
              )
            : self::money(
                self::money(max(0, (float) $fresh->remaining_balance)) +
                max(0, $remainingPenalty)
              );

        if ($totalOutstanding <= 0) {
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
