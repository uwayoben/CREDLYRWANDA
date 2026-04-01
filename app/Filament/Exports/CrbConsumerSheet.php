<?php

namespace App\Filament\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Sheet 1 — Consumer (Individual Borrowers)
 *
 * Location: app/Filament/Exports/CrbConsumerSheet.php
 *
 * Matches the official TransUnion CRB template exactly:
 *   Row 1  → 74 column headers  (no styling, plain data)
 *   Row 2+ → one row per loan
 *
 * Key CRB format rules observed from the original file:
 *   - Dates        → YYYYMMDD  (e.g. 20250331)
 *   - Currency     → RWF
 *   - Repayment    → MTH (monthly), WKL (weekly), DLY (daily), QTR (quarterly)
 *   - Account Type → A
 *   - Nature       → 13  (personal loan)
 *   - Category     → 1
 *   - Account Status:
 *       C = Current / Active
 *       W = Written Off
 *       D = Delinquent
 *       S = Settled / Completed
 *   - Current Balance Indicator:
 *       C = Credit (outstanding balance)
 *       N = Nil (zero balance)
 *   - Classification → normal | watch | substandard | doubtful | loss
 *   - Salutation    → MR | MRS | MS | DR | PROF
 *   - Gender        → M | F
 *   - Marital Status → S (Single) | M (Married) | W (Widowed) | D (Divorced)
 *   - Nationality   → RWANDA
 *   - Country       → RWANDAN
 */
class CrbConsumerSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    protected Collection $loans;
    protected string     $institutionName;
    protected string     $reportingDate;
    protected string     $institutionCode;

    public function __construct(
        Collection $loans,
        string     $institutionName,
        string     $reportingDate,
        string     $institutionCode = ''
    ) {
        $this->loans           = $loans;
        $this->institutionName = $institutionName;
        $this->reportingDate   = $reportingDate;
        $this->institutionCode = $institutionCode;
    }

    public function title(): string
    {
        return 'Consumer';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 74 CRB column headers — exact match to TransUnion template
    // ─────────────────────────────────────────────────────────────────────────

    private function headers(): array
    {
        return [
            'Salutation',                               // 01
            'Surname',                                  // 02
            'Forename or Initial 1',                    // 03
            'Forename or Initial 2',                    // 04
            'Forename or Initial 3',                    // 05
            'National ID Number',                       // 06
            'Passport No',                              // 07
            'Nationality',                              // 08
            'Tax No',                                   // 09
            'Driving License No',                       // 10
            'Social Security Number',                   // 11
            'Health Insurance Number',                  // 12
            'Marital Status',                           // 13
            'No of Dependants',                         // 14
            'Gender',                                   // 15
            'Date of Birth',                            // 16
            'Place Of Birth',                           // 17
            'Postal Address Line 1 Number',             // 18
            'Postal Address Line 2 Postal Code',        // 19
            'Physical Address Line 1',                  // 20
            'Physical Address Line 2',                  // 21
            'Physical Address Postal Code',             // 22
            'Physical Address Plot Number',             // 23
            'Physical Address Province',                // 24
            'Physical Address District',                // 25
            'Physical Address Sector',                  // 26
            'Physical Address Cell',                    // 27
            'Country',                                  // 28
            'Email Address',                            // 29
            'Residence Type',                           // 30
            'Work Telephone',                           // 31
            'Home Telephone',                           // 32
            'Mobile Telephone',                         // 33
            'Fascimile',                                // 34
            'Employer Name',                            // 35
            'Employer Address Line 1',                  // 36
            'Employer Address Line 2',                  // 37
            'Employer Town',                            // 38
            'Employer Country',                         // 39
            'Occupation',                               // 40
            'Income',                                   // 41
            'Income Frequency',                         // 42
            'Group Name',                               // 43
            'Group Number',                             // 44
            'Account Number',                           // 45
            'Old Account Number',                       // 46
            'Account Type',                             // 47
            'Account Status',                           // 48
            'Classification',                           // 49
            'Account Owner',                            // 50
            'Joint Loan Participants',                  // 51
            'Currency Type',                            // 52
            'Date Opened',                              // 53
            'Date Updated',                             // 54
            'Terms Duration',                           // 55
            'Repayment Term',                           // 56
            'Opening Balance / Credit Limit',           // 57
            'Current Balance',                          // 58
            'Available Credit',                         // 59
            'Current Balance Indicator',                // 60
            'Scheduled Monthly Payment Amount',         // 61
            'Actual Payment Amount',                    // 62
            'Amount Past Due',                          // 63
            'Installments in Arrears',                  // 64
            'Days in Arrears',                          // 65
            'Date Closed',                              // 66
            'Last Payment Date',                        // 67
            'Interest Rate',                            // 68
            'First Payment Date',                       // 69
            'Nature',                                   // 70
            'Category',                                 // 71
            'Sector of Activity',                       // 72
            'Approval Date',                            // 73
            'Final Payment Date',                       // 74
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Build array: header row + one data row per loan
    // ─────────────────────────────────────────────────────────────────────────

    public function array(): array
    {
        $rows   = [];
        $rows[] = $this->headers();

        foreach ($this->loans as $loan) {
            $rows[] = $this->mapLoan($loan);
        }

        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Map a single loan to the 74-column CRB row
    // ─────────────────────────────────────────────────────────────────────────

    private function mapLoan($loan): array
    {
        $customer = $loan->customer;

        // ── Name splitting ────────────────────────────────────────────────────
        // CRB requires Surname + up to 3 forenames separately
        $nameParts = $customer ? $this->splitName($customer->names ?? '') : ['', '', '', ''];
        $surname   = $nameParts[0];
        $fore1     = $nameParts[1];
        $fore2     = $nameParts[2];
        $fore3     = $nameParts[3];

        // ── Salutation ────────────────────────────────────────────────────────
        $salutation = $this->salutation(
            $customer?->gender ?? '',
            $customer?->marital_status ?? ''
        );

        // ── Dates in YYYYMMDD format ──────────────────────────────────────────
        $dob          = $customer?->date_of_birth
            ? Carbon::parse($customer->date_of_birth)->format('Ymd')
            : '';
        $dateOpened   = $loan->disbursement_date
            ? Carbon::parse($loan->disbursement_date)->format('Ymd')
            : ($loan->approved_at ? Carbon::parse($loan->approved_at)->format('Ymd') : '');
        $dateUpdated  = $loan->updated_at
            ? Carbon::parse($loan->updated_at)->format('Ymd')
            : $dateOpened;
        $approvalDate = $loan->approved_at
            ? Carbon::parse($loan->approved_at)->format('Ymd')
            : $dateOpened;
        $firstPayment = $loan->first_payment_date
            ? Carbon::parse($loan->first_payment_date)->format('Ymd')
            : '';
        $finalPayment = $loan->expected_completion_date
            ? Carbon::parse($loan->expected_completion_date)->format('Ymd')
            : '';
        $lastPayment  = $loan->payments && $loan->payments->isNotEmpty()
            ? Carbon::parse($loan->payments->sortByDesc('payment_date')->first()->payment_date)->format('Ymd')
            : $dateOpened;
        $dateClosed   = in_array($loan->loan_status, ['completed', 'written_off'])
            ? ($loan->completed_at ? Carbon::parse($loan->completed_at)->format('Ymd') : $this->reportingDate)
            : '';

        // ── Financial values ──────────────────────────────────────────────────
        $principal      = (float) $loan->principal_amount;
        $amountPaid     = (float) ($loan->amount_paid ?? 0);
        $currentBalance = max(0, $principal - (float) ($loan->principal_paid ?? 0));
        $amountPastDue  = (float) ($loan->arrears_amount ?? 0);
        $actualPayment  = $amountPaid > 0 ? $amountPaid : 0;

        // Scheduled monthly payment = EMI amount if available, else principal / installments
        $scheduledPayment = $loan->emi_amount
            ? (float) $loan->emi_amount
            : ($loan->number_of_installments > 0
                ? round($principal / $loan->number_of_installments, 2)
                : 0);

        // ── Installments in arrears ───────────────────────────────────────────
        $installmentsInArrears = $loan->installments
            ? $loan->installments->whereIn('status', ['overdue', 'partial'])->count()
            : 0;

        // ── Days in arrears ───────────────────────────────────────────────────
        $daysInArrears = $loan->date_when_arrears_start && $currentBalance > 0
            ? max(0, (int) Carbon::parse($loan->date_when_arrears_start)->diffInDays(now(), false))
            : 0;

        // ── Account Status ────────────────────────────────────────────────────
        // C=Current, S=Settled, W=Written Off, D=Delinquent
        $accountStatus = match ($loan->loan_status) {
            'completed'   => 'S',
            'written_off' => 'W',
            'defaulted'   => 'D',
            default       => 'C',
        };

        // ── Current Balance Indicator ─────────────────────────────────────────
        // C=Credit (has balance), N=Nil (zero balance)
        $balanceIndicator = $currentBalance > 0 ? 'C' : 'N';

        // ── Interest rate — stored as monthly %, CRB wants decimal ────────────
        $interestRate = round((float) $loan->interest_rate / 100, 4);

        // ── Repayment Term ────────────────────────────────────────────────────
        $repaymentTerm = $this->repaymentTerm($loan->installment_frequency ?? 'monthly');

        // ── Terms Duration (number of installments) ───────────────────────────
        $termsDuration = (int) ($loan->number_of_installments ?? 0);

        // ── Classification — CRB uses lowercase ──────────────────────────────
        $classification = $this->crbClassification($loan->loan_class ?? 'normal');

        // ── Account owner full name ───────────────────────────────────────────
        $accountOwner = $customer
            ? strtoupper(trim($customer->names ?? ''))
            : '';

        // ── Province ─────────────────────────────────────────────────────────
        $province = $this->provinceLabel($customer?->province ?? '');

        return [
            $salutation,                                        // 01 Salutation
            strtoupper($surname),                               // 02 Surname
            strtoupper($fore1),                                 // 03 Forename or Initial 1
            strtoupper($fore2),                                 // 04 Forename or Initial 2
            strtoupper($fore3),                                 // 05 Forename or Initial 3
            $customer?->national_id ?? '',                      // 06 National ID Number
            '0',                                                // 07 Passport No
            'RWANDA',                                           // 08 Nationality
            '',                                                 // 09 Tax No
            '',                                                 // 10 Driving License No
            '',                                                 // 11 Social Security Number
            '',                                                 // 12 Health Insurance Number
            $this->maritalStatus($customer?->marital_status ?? ''), // 13 Marital Status
            '',                                                 // 14 No of Dependants
            $this->gender($customer?->gender ?? ''),            // 15 Gender
            $dob,                                               // 16 Date of Birth (YYYYMMDD)
            strtoupper($customer?->district ?? ''),             // 17 Place Of Birth
            '',                                                 // 18 Postal Address Line 1
            '',                                                 // 19 Postal Address Line 2
            strtoupper($customer?->district ?? ''),             // 20 Physical Address Line 1
            '',                                                 // 21 Physical Address Line 2
            '',                                                 // 22 Physical Address Postal Code
            '',                                                 // 23 Physical Address Plot Number
            $province,                                          // 24 Physical Address Province
            strtoupper($customer?->district ?? ''),             // 25 Physical Address District
            strtoupper($customer?->sector ?? ''),               // 26 Physical Address Sector
            strtoupper($customer?->cell ?? ''),                 // 27 Physical Address Cell
            'RWANDAN',                                          // 28 Country
            $customer?->email ?? '',                            // 29 Email Address
            '',                                                 // 30 Residence Type
            $customer?->phone ?? '',                            // 31 Work Telephone
            $customer?->phone ?? '',                            // 32 Home Telephone
            $customer?->phone ?? '',                            // 33 Mobile Telephone
            '',                                                 // 34 Facsimile
            strtoupper($customer?->employer_name ?? ''),        // 35 Employer Name
            '',                                                 // 36 Employer Address Line 1
            '',                                                 // 37 Employer Address Line 2
            strtoupper($customer?->district ?? ''),             // 38 Employer Town
            '',                                                 // 39 Employer Country
            strtoupper($customer?->employment_status ?? 'private'), // 40 Occupation
            '',                                                 // 41 Income
            '',                                                 // 42 Income Frequency
            '',                                                 // 43 Group Name
            '',                                                 // 44 Group Number
            $loan->loan_number ?? '',                           // 45 Account Number
            '',                                                 // 46 Old Account Number
            'A',                                                // 47 Account Type
            $accountStatus,                                     // 48 Account Status
            $classification,                                    // 49 Classification
            $accountOwner,                                      // 50 Account Owner
            '',                                                 // 51 Joint Loan Participants
            'RWF',                                              // 52 Currency Type
            $dateOpened,                                        // 53 Date Opened (YYYYMMDD)
            $dateUpdated,                                       // 54 Date Updated (YYYYMMDD)
            $termsDuration,                                     // 55 Terms Duration
            $repaymentTerm,                                     // 56 Repayment Term
            $principal ?: '',                                   // 57 Opening Balance / Credit Limit
            $currentBalance ?: '',                              // 58 Current Balance
            '',                                                 // 59 Available Credit
            $balanceIndicator,                                  // 60 Current Balance Indicator
            $scheduledPayment ?: '',                            // 61 Scheduled Monthly Payment Amount
            $actualPayment ?: '0',                              // 62 Actual Payment Amount
            $amountPastDue ?: '0',                              // 63 Amount Past Due
            $installmentsInArrears,                             // 64 Installments in Arrears
            $daysInArrears,                                     // 65 Days in Arrears
            $dateClosed,                                        // 66 Date Closed (YYYYMMDD)
            $lastPayment,                                       // 67 Last Payment Date (YYYYMMDD)
            $interestRate,                                      // 68 Interest Rate
            $firstPayment,                                      // 69 First Payment Date (YYYYMMDD)
            '13',                                               // 70 Nature (13 = personal loan)
            '1',                                                // 71 Category
            strtoupper($customer?->district ?? ''),             // 72 Sector of Activity
            $approvalDate,                                      // 73 Approval Date (YYYYMMDD)
            $finalPayment,                                      // 74 Final Payment Date (YYYYMMDD)
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Styling
    // ─────────────────────────────────────────────────────────────────────────

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $count   = $this->loans->count();
                $lastRow = 1 + $count;
                $lastCol = 'BV'; // column 74

                // Tab colour — TransUnion blue
                $sheet->getTabColor()->setRGB('003087');

                // ── Column widths ─────────────────────────────────────────────
                // Identity columns
                foreach (['A','B','C','D','E'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(14);
                }
                $sheet->getColumnDimension('F')->setWidth(20); // National ID
                $sheet->getColumnDimension('G')->setWidth(10);
                $sheet->getColumnDimension('H')->setWidth(10);
                // Address columns
                foreach (['T','U','V','W','X','Y','Z','AA','AB'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(14);
                }
                // Account columns
                $sheet->getColumnDimension('AS')->setWidth(16); // Account Number
                $sheet->getColumnDimension('AV')->setWidth(10); // Account Type
                $sheet->getColumnDimension('AW')->setWidth(10); // Account Status
                $sheet->getColumnDimension('AX')->setWidth(14); // Classification
                $sheet->getColumnDimension('AY')->setWidth(22); // Account Owner
                // Financial columns
                foreach (['BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(16);
                }

                // ── Row 1: column headers ─────────────────────────────────────
                $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003087']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '1A5276']],
                    ],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(40);

                // Freeze header row
                $sheet->freezePane('A2');

                if ($count === 0) {
                    $sheet->mergeCells("A2:{$lastCol}2");
                    $sheet->setCellValue('A2', 'No individual loans found for this reporting period.');
                    $sheet->getStyle('A2')->applyFromArray([
                        'font'      => ['italic' => true, 'color' => ['rgb' => '888888']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    return;
                }

                // ── Data rows ─────────────────────────────────────────────────
                for ($r = 2; $r <= $lastRow; $r++) {
                    // Alternate row shading
                    $bg = $r % 2 === 0 ? 'EBF5FB' : 'FFFFFF';
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D6EAF8']],
                        ],
                    ]);

                    // Number format — financial columns
                    // Col 57=BE, 58=BF, 61=BI, 62=BJ, 63=BK
                    foreach (['BE', 'BF', 'BI', 'BJ', 'BK'] as $col) {
                        $sheet->getStyle("{$col}{$r}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0');
                        $sheet->getStyle("{$col}{$r}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    // Interest rate as decimal (e.g. 0.15 stays as 0.15)
                    $sheet->getStyle("BP{$r}")
                        ->getNumberFormat()
                        ->setFormatCode('0.0000');

                    // Centre-align status/type columns
                    foreach (['AV', 'AW', 'AX', 'BA', 'BH', 'BN', 'BO'] as $col) {
                        $sheet->getStyle("{$col}{$r}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    // Colour-code Classification column (AX = col 49)
                    $classification = $sheet->getCell("AX{$r}")->getValue();
                    $classColor = match ($classification) {
                        'normal'      => '1E8449',
                        'watch'       => 'D4AC0D',
                        'substandard' => 'CA6F1E',
                        'doubtful'    => 'CB4335',
                        'loss'        => '7B241C',
                        default       => '555555',
                    };
                    $sheet->getStyle("AX{$r}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $classColor]],
                    ]);

                    // Colour-code Account Status column (AW = col 48)
                    $status = $sheet->getCell("AW{$r}")->getValue();
                    $statusColor = match ($status) {
                        'C' => 'D5F5E3',
                        'S' => 'D6EAF8',
                        'W' => 'FADBD8',
                        'D' => 'FDEBD0',
                        default => 'FFFFFF',
                    };
                    $sheet->getStyle("AW{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $statusColor]],
                        'font' => ['bold' => true],
                    ]);

                    $sheet->getRowDimension($r)->setRowHeight(15);
                }

                // ── Outline border around entire table ────────────────────────
                $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003087']],
                    ],
                ]);
            },
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Split a full name into [surname, fore1, fore2, fore3].
     * CRB convention: first part = surname, rest = forenames.
     */
    private function splitName(string $fullName): array
    {
        $parts = array_filter(explode(' ', trim($fullName)));
        $parts = array_values($parts);

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
            $parts[2] ?? '',
            $parts[3] ?? '',
        ];
    }

    /** CRB salutation based on gender and marital status */
    private function salutation(string $gender, string $maritalStatus): string
    {
        $g = strtolower($gender);
        $m = strtolower($maritalStatus);

        if ($g === 'male') return 'MR';
        if ($g === 'female') {
            return match ($m) {
                'married' => 'MRS',
                'widowed' => 'MRS',
                default   => 'MS',
            };
        }
        return 'MS';
    }

    /** CRB gender code: M or F */
    private function gender(string $gender): string
    {
        return match (strtolower($gender)) {
            'male'   => 'M',
            'female' => 'F',
            default  => '',
        };
    }

    /** CRB marital status code: S, M, W, D */
    private function maritalStatus(string $status): string
    {
        return match (strtolower($status)) {
            'single'   => 'S',
            'married'  => 'M',
            'widowed'  => 'W',
            'divorced' => 'D',
            default    => 'S',
        };
    }

    /** CRB repayment term code */
    private function repaymentTerm(string $frequency): string
    {
        return match ($frequency) {
            'daily'     => 'DLY',
            'weekly'    => 'WKL',
            'bi_weekly' => 'WKL',
            'monthly'   => 'MTH',
            'quarterly' => 'QTR',
            default     => 'MTH',
        };
    }

    /** CRB classification — lowercase as per TransUnion spec */
    private function crbClassification(string $class): string
    {
        return match ($class) {
            'normal'      => 'normal',
            'watch'       => 'watch',
            'substandard' => 'substandard',
            'doubtful'    => 'doubtful',
            'loss'        => 'loss',
            'written_off' => 'loss',
            'restructured'=> 'normal',
            default       => 'normal',
        };
    }

    /** Province label — capitalised as seen in the CRB original */
    private function provinceLabel(string $province): string
    {
        return match (strtolower($province)) {
            'kigali', 'kigali city' => 'Kigali City',
            'northern'              => 'Northern',
            'southern'              => 'Southern',
            'eastern'               => 'Eastern',
            'western'               => 'Western',
            default                 => ucfirst($province),
        };
    }
}
