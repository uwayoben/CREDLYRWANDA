<?php

namespace App\Imports;

use App\Models\Customer;
use App\Models\Loan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class LoansImport implements ToCollection, WithHeadingRow
{
    public int   $importedCount = 0;
    public int   $skippedCount  = 0;
    public array $errors        = [];

    protected int  $companyId;
    protected ?int $defaultUserId;

    public function __construct(?int $companyId = null)
    {
        $this->companyId     = $companyId ?? Auth::user()?->company_id ?? 0;
        $this->defaultUserId = Auth::user()?->id;
    }

    public function headingRow(): int
    {
        return 1;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $row       = $row->toArray();
            $rowNumber = $index + 2;

            // ── Get national ID ───────────────────────────────────────────────
            $nationalId = trim((string) ($row['customer_national_id'] ?? ''));

            // Remove Excel decimal if any (e.g. 1198780173056016.0)
            $nationalId = rtrim(rtrim($nationalId, '0'), '.');

            if ($nationalId === '') {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: No national ID — skipped.";
                continue;
            }

            // ── Find customer by national ID ──────────────────────────────────
            $customer = Customer::where('company_id', $this->companyId)
                ->where(function ($q) use ($nationalId) {
                    $q->where('national_id', $nationalId)
                      ->orWhere('national_id', (string) (int) $nationalId)
                      ->orWhereRaw('CAST(national_id AS CHAR) = ?', [$nationalId]);
                })
                ->first();

            if (! $customer) {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: Customer NID [{$nationalId}] not found — skipped.";
                continue;
            }

            // ── Skip duplicate loan number ────────────────────────────────────
            $loanNumber = trim((string) ($row['loan_number'] ?? ''));
            if ($loanNumber && Loan::where('loan_number', $loanNumber)->exists()) {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: Loan [{$loanNumber}] already exists — skipped.";
                continue;
            }

            // ── Save loan directly — no recalculation ─────────────────────────
            try {
                $principal  = (float) ($row['principal_amount']  ?? 0);
                $amountPaid = (float) ($row['amount_paid']       ?? 0);
                $remaining  = (float) ($row['remaining_balance'] ?? max(0, $principal - $amountPaid));

                Loan::create([
                    'company_id'               => $this->companyId,
                    'customer_id'              => $customer->id,
                    'created_by'               => $this->defaultUserId,
                    'loan_number'              => $loanNumber ?: ('LN-IMP-' . str_pad($rowNumber, 4, '0', STR_PAD_LEFT)),
                    'principal_amount'         => $principal,
                    'interest_rate'            => (float) ($row['interest_rate']           ?? 0),
                    'interest_type'            => $this->mapInterestType($row['interest_type'] ?? 'declining'),
                    'number_of_installments'   => max(1, (int) ($row['number_of_installments'] ?? 1)),
                    'installment_frequency'    => $this->mapFrequency($row['installment_frequency'] ?? 'monthly'),
                    'penalty_rate'             => (float) ($row['penalty_rate']            ?? 0),

                    // ── Figures saved exactly as provided ─────────────────────
                    'total_interest'           => (float) ($row['total_interest']   ?? 0),
                    'total_amount'             => (float) ($row['total_amount']     ?? $principal),
                    'emi_amount'               => (float) ($row['emi_amount']       ?? 0),
                    'amount_paid'              => $amountPaid,
                    'principal_paid'           => (float) ($row['principal_paid']   ?? 0),
                    'interest_paid'            => (float) ($row['interest_paid']    ?? 0),
                    'penalty_paid'             => (float) ($row['penalty_paid']     ?? 0),
                    'remaining_balance'        => $remaining,

                    // ── Status & class ────────────────────────────────────────
                    'loan_status'              => $this->mapStatus($row['loan_status'] ?? 'active'),
                    'loan_class'               => $this->mapClass($row['loan_class']   ?? 'normal'),

                    // ── Dates ─────────────────────────────────────────────────
                    'disbursement_date'        => $this->parseDate($row['disbursement_date']         ?? null),
                    'first_payment_date'       => $this->parseDate($row['first_payment_date']        ?? null),
                    'expected_completion_date' => $this->parseDate($row['expected_completion_date']  ?? null),
                    'last_payment_date'        => $this->parseDate($row['last_payment_date']         ?? null),
                    'approved_at'              => $this->parseDate($row['disbursement_date']         ?? null),
                    'disbursed_at'             => $this->parseDate($row['disbursement_date']         ?? null),

                    // ── Other ─────────────────────────────────────────────────
                    'purpose'                  => $row['purpose']         ?? null,
                    'guarantee_collateral'     => $row['collateral_type'] ?? null,
                ]);

                $this->importedCount++;

            } catch (\Exception $e) {
                $this->skippedCount++;
                $this->errors[] = "Row {$rowNumber}: [{$nationalId}] — " . $e->getMessage();
                Log::error('LoansImport error', ['row' => $rowNumber, 'error' => $e->getMessage()]);
            }
        }
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function mapStatus(?string $val): string
    {
        return match (strtolower(trim((string) $val))) {
            'active', 'disbursed'         => 'active',
            'completed', 'paid', 'closed' => 'completed',
            'defaulted', 'default'        => 'defaulted',
            'written_off', 'written off'  => 'written_off',
            'pending'                     => 'pending',
            default                       => 'active',
        };
    }

    private function mapClass(?string $val): string
    {
        return match (strtolower(trim((string) $val))) {
            'normal'                      => 'normal',
            'watch'                       => 'watch',
            'substandard', 'sub-standard' => 'substandard',
            'doubtful'                    => 'doubtful',
            'loss'                        => 'loss',
            'restructured'                => 'restructured',
            'written_off', 'written off'  => 'written_off',
            default                       => 'normal',
        };
    }

    private function mapInterestType(?string $val): string
    {
        $v = strtolower(trim((string) $val));
        if (str_contains($v, 'flat'))   return 'flat';
        if (str_contains($v, 'declin')) return 'declining';
        return 'declining';
    }

    private function mapFrequency(?string $val): string
    {
        return match (strtolower(trim((string) $val))) {
            'daily'                    => 'daily',
            'weekly'                   => 'weekly',
            'bi_weekly', 'bi-weekly'   => 'bi_weekly',
            'monthly'                  => 'monthly',
            'quarterly'                => 'quarterly',
            default                    => 'monthly',
        };
    }

    private function parseDate(mixed $value): ?string
    {
        if (empty($value)) return null;

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)
                    ->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        $value = trim((string) $value);
        foreach (['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Ymd'] as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                if ($date) return $date->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }
}
