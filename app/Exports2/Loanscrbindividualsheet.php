<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LoansCrbIndividualSheet implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithTitle
{
    protected Collection $loans;
    protected string     $institutionName;
    protected string     $reportingDate;

    public function __construct(Collection $loans, string $institutionName, string $reportingDate)
    {
        $this->loans           = $loans;
        $this->institutionName = $institutionName;
        $this->reportingDate   = $reportingDate;
    }

    public function title(): string
    {
        // Sheet name matches TransUnion format: CRBIC + YYYYMMDD + institution code
        return 'CRBIC' . now()->format('Ymd') . '000001';
    }

    public function collection(): Collection
    {
        return $this->loans;
    }

    public function headings(): array
    {
        return [
            'Salutation',
            'Surname',
            'Forename or Initial 1',
            'Forename or Initial 2',
            'Forename or Initial 3',
            'National ID Number',
            'Passport No',
            'Nationality',
            'Tax No',
            'Driving License No',
            'Social Security Number',
            'Health Insurance Number',
            'Marital Status',
            'No of Dependants',
            'Gender',
            'Date of Birth',
            'Place Of Birth',
            'Postal Address Line 1 Number',
            'Postal Address Line 2 Postal Code',
            'Physical Address Line 1',
            'Physical Address Line 2',
            'Physical Address Postal Code',
            'Physical Address Plot Number',
            'Physical Address Province',
            'Physical Address District',
            'Physical Address Sector',
            'Physical Address Cell',
            'Country',
            'Email Address',
            'Residence Type',
            'Work Telephone',
            'Home Telephone',
            'Mobile Telephone',
            'Fascimile',
            'Employer Name',
            'Employer Address Line 1',
            'Employer Address Line 2',
            'Employer Town',
            'Employer Country',
            'Occupation',
            'Income',
            'Income Frequency',
            'Group Name',
            'Group Number',
            'Account Number',
            'Old Account Number',
            'Account Type',
            'Account Status',
            'Classification',
            'Account Owner',
            'Joint Loan Participants',
            'Currency Type',
            'Date Opened',
            'Date Updated',
            'Terms Duration',
            'Repayment Term',
            'Opening Balance / Credit Limit',
            'Current Balance',
            'Available Credit',
            'Current Balance Indicator',
            'Scheduled Monthly Payment Amount',
            'Actual Payment Amount',
            'Amount Past Due',
            'Installments in Arrears',
            'Days in Arrears',
            'Date Closed',
            'Last Payment Date',
            'Interest Rate',
            'First Payment Date',
            'Nature',
            'Category',
            'Sector of Activity',
            'Approval Date',
            'Final Payment Date',
        ];
    }

    public function map($loan): array
    {
        $customer    = $loan->customer;
        $names       = $customer?->names ?? '';
        $nameParts   = $this->splitName($names);
        $salutation  = $this->getSalutation($customer?->gender, $customer?->marital_status);
        $outstanding = max(0, (float) $loan->total_amount - (float) ($loan->amount_paid ?? 0));
        $daysArrears = $this->calcDaysInArrears($loan);
        $installArrears = $daysArrears > 0 ? (int) ceil($daysArrears / 30) : 0;

        return [
            $salutation,                                                    // Salutation
            $nameParts['surname'],                                          // Surname
            $nameParts['forename1'],                                        // Forename 1
            $nameParts['forename2'],                                        // Forename 2
            $nameParts['forename3'],                                        // Forename 3
            $customer?->national_id ?? '0',                                 // National ID
            '0',                                                            // Passport No
            'RWANDA',                                                       // Nationality
            null,                                                           // Tax No
            null,                                                           // Driving License
            null,                                                           // Social Security
            null,                                                           // Health Insurance
            $this->mapMaritalStatus($customer?->marital_status),           // Marital Status
            null,                                                           // No of Dependants
            $this->mapGender($customer?->gender),                          // Gender
            $customer?->date_of_birth?->format('Ymd'),                     // Date of Birth
            $customer?->district ?? null,                                   // Place of Birth
            null,                                                           // Postal Address Line 1
            null,                                                           // Postal Address Line 2
            $customer?->district ?? null,                                   // Physical Address Line 1
            null,                                                           // Physical Address Line 2
            null,                                                           // Physical Address Postal Code
            null,                                                           // Physical Address Plot No
            $customer?->province ?? null,                                   // Province
            $customer?->district ?? null,                                   // District
            $customer?->sector ?? null,                                     // Sector
            $customer?->cell ?? null,                                       // Cell
            'RWANDA',                                                       // Country
            $customer?->email ?? null,                                      // Email
            null,                                                           // Residence Type
            $customer?->phone ?? null,                                      // Work Telephone
            $customer?->phone ?? null,                                      // Home Telephone
            $customer?->phone ?? null,                                      // Mobile Telephone
            null,                                                           // Facsimile
            $customer?->employer_name ?? null,                              // Employer Name
            null,                                                           // Employer Address Line 1
            null,                                                           // Employer Address Line 2
            null,                                                           // Employer Town
            null,                                                           // Employer Country
            'private',                                                      // Occupation
            null,                                                           // Income
            null,                                                           // Income Frequency
            $this->institutionName,                                         // Group Name
            '1',                                                            // Group Number
            $loan->loan_number,                                             // Account Number
            null,                                                           // Old Account Number
            'A',                                                            // Account Type (A=Asset/Loan)
            $this->mapAccountStatus($loan->loan_status),                   // Account Status
            $this->mapClassification($loan->loan_class),                   // Classification
            $this->institutionName,                                         // Account Owner
            null,                                                           // Joint Loan Participants
            'RWF',                                                          // Currency Type
            $loan->disbursement_date?->format('Ymd'),                      // Date Opened
            now()->format('Ymd'),                                           // Date Updated
            $loan->number_of_installments,                                 // Terms Duration
            'MTH',                                                          // Repayment Term
            $loan->principal_amount,                                        // Opening Balance
            $outstanding,                                                   // Current Balance
            null,                                                           // Available Credit
            $this->mapBalanceIndicator($loan->loan_status),                // Current Balance Indicator
            $this->calcMonthlyInstallment($loan),                          // Scheduled Monthly Payment
            $loan->payments()->latest()->first()?->amount ?? '0',          // Actual Payment Amount
            $loan->arrears_amount ?? '0',                                   // Amount Past Due
            $installArrears,                                                // Installments in Arrears
            $daysArrears,                                                   // Days in Arrears
            $loan->completed_at?->format('Ymd'),                           // Date Closed
            $loan->payments()->latest()->first()?->payment_date?->format('Ymd'), // Last Payment Date
            $loan->interest_rate,                                           // Interest Rate
            $loan->first_payment_date?->format('Ymd'),                     // First Payment Date
            '11',                                                           // Nature
            '11',                                                           // Category
            $customer?->district ?? 'RWANDA',                              // Sector of Activity
            $loan->disbursement_date?->format('Ymd'),                      // Approval Date
            $loan->last_payment_date?->format('Ymd'),                      // Final Payment Date
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = 'BV'; // 74 columns
        $lastRow = $this->loans->count() + 1;

        // Header row
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A3C6E']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);
        $sheet->getRowDimension(1)->setRowHeight(40);
        $sheet->freezePane('A2');

        // Alternate row shading
        for ($i = 2; $i <= $lastRow; $i++) {
            $color = $i % 2 === 0 ? 'EAF0FB' : 'FFFFFF';
            $sheet->getStyle("A{$i}:{$lastCol}{$i}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // Borders
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D0D7E8']],
                'outline'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '1A3C6E']],
            ],
        ]);

        return [];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function splitName(string $fullName): array
    {
        $parts = array_values(array_filter(explode(' ', trim($fullName))));
        return [
            'surname'   => strtoupper($parts[0] ?? ''),
            'forename1' => strtoupper($parts[1] ?? ''),
            'forename2' => strtoupper($parts[2] ?? null) ?: null,
            'forename3' => strtoupper($parts[3] ?? null) ?: null,
        ];
    }

    private function getSalutation(?string $gender, ?string $marital): string
    {
        $g = strtolower($gender ?? '');
        $m = strtolower($marital ?? '');
        if ($g === 'f' || $g === 'female') {
            return ($m === 'married') ? 'MRS ' : 'MS';
        }
        return 'MS';
    }

    private function mapGender(?string $gender): string
    {
        return match (strtolower($gender ?? '')) {
            'male', 'm'     => 'M',
            'female', 'f'   => 'F',
            default         => 'M',
        };
    }

    private function mapMaritalStatus(?string $status): string
    {
        return match (strtolower($status ?? '')) {
            'married'  => 'MARIED',
            'single'   => 'SINGLE',
            'divorced' => 'DIVORCED',
            'widowed'  => 'WIDOWED',
            default    => 'SINGLE',
        };
    }

    private function mapAccountStatus(?string $status): string
    {
        return match ($status) {
            'active', 'disbursed' => 'A',  // Active
            'completed'           => 'C',  // Closed
            'defaulted'           => 'D',  // Default
            'written_off'         => 'W',  // Written off
            'pending', 'approved' => 'P',  // Pending
            default               => 'A',
        };
    }

    private function mapClassification(?string $class): string
    {
        return match ($class) {
            'normal'      => 'ZCSS',
            'watch'       => 'ZCSS',
            'substandard' => 'ZSUB',
            'doubtful'    => 'ZDBT',
            'loss'        => 'ZLSS',
            'written_off' => 'ZWOF',
            default       => 'ZCSS',
        };
    }

    private function mapBalanceIndicator(?string $status): string
    {
        return match ($status) {
            'completed'   => 'C', // Credit (fully paid)
            'defaulted'   => 'D', // Debit (overdue)
            default       => 'C',
        };
    }

    private function calcMonthlyInstallment($loan): float
    {
        $principal = (float) $loan->principal_amount;
        $rate      = (float) $loan->interest_rate / 100;
        $n         = (int) $loan->number_of_installments;

        if ($n <= 0) return 0;

        if ($loan->interest_type === 'flat') {
            return round(($principal + ($principal * $rate * $n)) / $n, 2);
        }

        if ($rate == 0) return round($principal / $n, 2);

        return round(($principal * $rate * pow(1 + $rate, $n)) / (pow(1 + $rate, $n) - 1), 2);
    }

    private function calcDaysInArrears($loan): int
    {
        if (! $loan->date_when_arrears_start) return 0;
        if (! in_array($loan->loan_status, ['active', 'defaulted', 'arrears'])) return 0;
        return max(0, (int) Carbon::parse($loan->date_when_arrears_start)->diffInDays(now(), false));
    }
}