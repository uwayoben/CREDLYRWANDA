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
 * Sheets 3–8 — A1.3 Normal Loans through A1.8 Restructured loans
 *
 * Location: app/Filament/Exports/BnrClassSheet.php
 *
 * One instance is created per loan class.
 * Matches BNR original exactly:
 *
 *   Rows 1–5  → institution info block
 *   Row  6    → blank
 *   Row  7    → blank (col O holds collateral reference list)
 *   Row  8    → blank
 *   Row  9    → PAR label + minimum provisioning rate
 *   Row  10   → 41 column headers  (A–AP)
 *   Row  11   → column numbers     (amber background FFC000)
 *   Row  12+  → loan data rows
 *   Last row  → TOTALS
 *
 * BNR collateral eligibility weights (for column AI):
 *   Cash collateral / Govt / Bank securities → 100 %
 *   Land and Building                        →  60 %
 *   Movable collaterals                      →  40 %
 *   Biological / Other assets                →  40 %
 */
class BnrClassSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    protected Collection $loans;
    protected string     $classKey;
    protected array      $meta;
    protected string     $institutionName;
    protected string     $reportingDate;

    // Collateral types shown in column O rows 2–8 (BNR reference area)
    private const COLLATERAL_TYPES = [
        'cash collateral',
        'Government or the Central Bank Bills and Bonds ',
        'Other securities offered by the banks operating in Rwanda',
        'Land and Building',
        'movable collaterals.',
        'Biological assets',
        'Other assets ',
    ];

    // Column numbers label row (row 11) — exactly 42 values for columns A–AP
    private const COL_NUMBERS = [
        'Column1',   'Column2',   'Column3',   'Column4',   'Column5',
        'Column 6',  'Column 7',  'Column 8',  'Column 9',  'Column10',
        'Column11',  'Column12',  'Column 12', 'Column13',  'Column14',
        'Column15',  'Column16',  'Column17',  'Column18',  'Column19',
        'Column 20', 'Column 21', 'Column 22', 'Column 23', 'Column 24',
        'Column 25', 'Column 26', 'Column 27', 'Column 28', 'Column 29',
        'Column 30', 'Column 31', 'Column 32', 'Column 33', 'Column 34',
        'Column 35', 'Column 36', 'Column 37', 'Column 38', 'Column 39',
        'Column 40', 'Column 41',
    ];

    public function __construct(
        Collection $loans,
        string     $classKey,
        array      $meta,
        string     $institutionName,
        string     $reportingDate
    ) {
        $this->loans           = $loans;
        $this->classKey        = $classKey;
        $this->meta            = $meta;
        $this->institutionName = $institutionName;
        $this->reportingDate   = $reportingDate;
    }

    public function title(): string
    {
        return $this->meta['sheet'];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 41 BNR column headers (row 10)
    // ─────────────────────────────────────────────────────────────────────────

    private function headers(): array
    {
        return [
            'No',                                                           // A
            'Names of Borrowers',                                           // B
            'ID of the Borrower',                                           // C
            'Telephone number',                                             // D
            'Gender',                                                       // E
            'Age',                                                          // F
            'Relationship with the NDFSP ( Staff, Director, Shareholder…)',// G
            'Marital Status (Married/Single/Widow)',                        // H
            'previous loans paid on time (Yes/No)',                         // I
            'Purpose of the loan',                                          // J
            'Branch name',                                                  // K
            'Collateral Type',                                              // L
            'Guarantee(Collateral) Ammount',                                // M
            "Borrower's District",                                          // N
            "Borrower's Sector",                                            // O
            "Borrower's Cell",                                              // P
            "Borrower's Village",                                           // Q
            'Annual Interest Rate',                                         // R
            'Method of interest rate calculation (Flat/Declining)',         // S
            'Names of the Loan Officer',                                    // T
            'Disbursed amount',                                             // U
            'Date of loan disbursement',                                    // V
            'Agreed Maturity Date',                                         // W
            'Agreed Frequency of Repayment (Days)',                         // X
            'Grace Period Accorded (Days)',                                  // Y
            'Agreed Date of First Payment (Principal)',                     // Z
            'Date of Last Payment (Principal)',                             // AA
            'Date when Arrears Start',                                      // AB
            'Cut Off Date (Report Date)',                                   // AC
            'Total Number of Installments',                                 // AD
            'Round Number of Installments  paid',                           // AE
            'Round Number of Installments outstanding',                     // AF
            'Amount Repaid (Principal)',                                     // AG
            'Balance Outstanding (Principal)',                              // AH
            'Eligible Collateral provided ',                                // AI
            'Net Amount due (Principal)',                                   // AJ
            'Number of days overdue (Arrears) ',                            // AK
            'Class',                                                        // AL
            'Provisioning Rate (Regulation)',                               // AM
            'Provision Required ',                                          // AN
            'Previous Provisions',                                          // AO
            'Additional Provisions',                                        // AP
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Build the full sheet array
    // ─────────────────────────────────────────────────────────────────────────

    public function array(): array
    {
        $rows  = [];
        $today = now()->format('d/m/Y');
        $rate  = $this->meta['rate'];
        $ct    = self::COLLATERAL_TYPES;

        // ── Rows 1–8: institution info block ─────────────────────────────────
        // BNR places collateral reference values in column O (index 14) of rows 2–8
        $rows[] = $this->infoRow(['NDFSP Name', null, $this->institutionName],              14, $ct[0] ?? '');
        $rows[] = $this->infoRow([$this->institutionName],                                   14, $ct[1] ?? '');
        $rows[] = $this->infoRow([$this->institutionName, null, $this->reportingDate],       14, $ct[2] ?? '');
        $rows[] = $this->infoRow(['Report Name ', null, $this->meta['label']],               14, $ct[3] ?? '');
        $rows[] = $this->infoRow([null],                                                     14, $ct[4] ?? '');
        $rows[] = $this->infoRow([null],                                                     14, $ct[5] ?? '');
        $rows[] = $this->infoRow([null],                                                     14, $ct[6] ?? '');
        $rows[] = array_fill(0, 42, null); // row 8 blank

        // ── Row 9: PAR + provisioning label ──────────────────────────────────
        $row9    = array_fill(0, 42, null);
        $row9[0] = ' ';
        $row9[1] = $this->meta['par'];
        $row9[2] = $this->meta['prov'];
        $rows[]  = $row9;

        // ── Row 10: column headers ────────────────────────────────────────────
        $rows[] = $this->headers();

        // ── Row 11: column numbers (amber) ────────────────────────────────────
        $rows[] = self::COL_NUMBERS;

        // ── Rows 12+: loan data ───────────────────────────────────────────────
        foreach ($this->loans as $i => $loan) {
            $rows[] = $this->mapLoan($loan, $i + 1, $today, $rate);
        }

        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Map a single loan to the 42-column BNR row
    // ─────────────────────────────────────────────────────────────────────────

    private function mapLoan($loan, int $no, string $today, float $rate): array
    {
        $customer  = $loan->customer;
        $principal = (float) $loan->principal_amount;
        $paid      = (float) ($loan->principal_paid ?? 0);
        $balance   = max(0, $principal - $paid);

        // Collateral
        $collateralAmt  = (float) ($loan->collateral_value ?? 0);
        $collateralType = $this->collateralLabel($loan->guarantee_collateral ?? '');
        $eligiblePct    = $this->eligiblePct($loan->guarantee_collateral ?? '');
        $eligible       = round($collateralAmt * $eligiblePct, 2);
        $netDue         = max(0, $balance - $eligible);

        // Provision
        $provision = $netDue > 0 ? round($netDue * $rate, 2) : 0;

        // Installments
        $totalInst = (int) ($loan->number_of_installments ?? 0);
        $paidInst  = $loan->installments
            ? $loan->installments->where('status', 'paid')->count()
            : 0;
        $outInst   = max(0, $totalInst - $paidInst);

        // Days overdue
        $daysOverdue = $this->daysOverdue($loan);

        // Annual interest rate = monthly rate × 12
        $annualRate = round((float) $loan->interest_rate / 100 * 12, 4);

       $age = '';

if (!empty($customer?->date_of_birth)) {
    try {
        $dob = Carbon::parse($customer->date_of_birth);

        if ($dob->isPast()) {
            $age = $dob->age; // ✅ best way (always integer)
        }
    } catch (\Exception $e) {
        $age = '';
    }
}

        return [
            $no,                                                                            // A  No
            $customer?->names ?? '',                                                        // B  Names of Borrowers
            $customer?->national_id ?? '',                                                  // C  ID of the Borrower
            $customer?->phone ?? '',                                                        // D  Telephone number
            ucfirst($customer?->gender ?? ''),                                              // E  Gender
            $age,                                                      // F  Age,
            'No',                                                                       // G  Relationship with NDFSP
            ucfirst($customer?->marital_status ?? ''),                                      // H  Marital Status
            '',                                                                             // I  Previous loans paid on time
            $loan->purpose ?? '',                                                           // J  Purpose of the loan
            '',                                                                             // K  Branch name
            $collateralType,                                                                // L  Collateral Type
            $collateralAmt ?: null,                                                         // M  Guarantee(Collateral) Amount
            $customer?->district ?? '',                                                     // N  Borrower's District
            $customer?->sector ?? '',                                                       // O  Borrower's Sector
            $customer?->cell ?? '',                                                         // P  Borrower's Cell
            $customer?->village ?? '',                                                      // Q  Borrower's Village
            $annualRate,                                                                    // R  Annual Interest Rate
            $loan->interest_type === 'flat' ? 'Flat' : 'Declining',                        // S  Method of calculation
            $loan->createdBy?->name ?? '',                                                  // T  Names of the Loan Officer
            $principal ?: null,                                                             // U  Disbursed amount
            $loan->disbursement_date?->format('d/m/Y') ?? '',                              // V  Date of loan disbursement
            $loan->expected_completion_date?->format('d/m/Y') ?? '',                       // W  Agreed Maturity Date
            $this->frequencyDays($loan->installment_frequency ?? 'monthly'),               // X  Agreed Frequency (Days)
            0,                                                                              // Y  Grace Period (Days)
            $loan->first_payment_date?->format('d/m/Y') ?? '',                             // Z  Agreed Date of First Payment
            $loan->expected_completion_date?->format('d/m/Y') ?? '',                       // AA Date of Last Payment
            $loan->date_when_arrears_start?->format('d/m/Y') ?? '',                        // AB Date when Arrears Start
            $today,                                                                         // AC Cut Off Date (Report Date)
            $totalInst ?: null,                                                             // AD Total Number of Installments
            $paidInst ?: null,                                                              // AE Round Number of Installments paid
            $outInst ?: null,                                                               // AF Round Number of Installments outstanding
            $paid ?: null,                                                                  // AG Amount Repaid (Principal)
            $balance ?: null,                                                               // AH Balance Outstanding (Principal)
            $eligible ?: null,                                                              // AI Eligible Collateral provided
            $netDue ?: null,                                                                // AJ Net Amount due (Principal)
            $daysOverdue ?: null,                                                           // AK Number of days overdue
            ucfirst($loan->loan_class ?? $this->classKey),                                  // AL Class
            $rate,                                                                          // AM Provisioning Rate (Regulation)
            $provision ?: null,                                                             // AN Provision Required
            null,                                                                           // AO Previous Provisions
            $provision ?: null,                                                             // AP Additional Provisions
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Styling & events
    // ─────────────────────────────────────────────────────────────────────────

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $count   = $this->loans->count();
                $lastCol = 'AP';
                $dataEnd = 11 + $count; // row 11 = col-number row, data starts row 12

                // Tab colour
                $sheet->getTabColor()->setRGB($this->tabColor());

                // ── Column widths ─────────────────────────────────────────────
                $widths = [
                    'A' => 5,  'B' => 26, 'C' => 20, 'D' => 15, 'E' => 8,
                    'F' => 6,  'G' => 20, 'H' => 16, 'I' => 12, 'J' => 22,
                    'K' => 14, 'L' => 20, 'M' => 15, 'N' => 14, 'O' => 14,
                    'P' => 12, 'Q' => 14, 'R' => 12, 'S' => 14, 'T' => 22,
                    'U' => 16, 'V' => 14, 'W' => 14, 'X' => 12, 'Y' => 10,
                    'Z' => 14, 'AA'=> 14, 'AB'=> 14, 'AC'=> 14, 'AD'=> 10,
                    'AE'=> 10, 'AF'=> 10, 'AG'=> 16, 'AH'=> 16, 'AI'=> 16,
                    'AJ'=> 16, 'AK'=> 12, 'AL'=> 14, 'AM'=> 12, 'AN'=> 16,
                    'AO'=> 16, 'AP'=> 16,
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // ── Rows 1–8: info block ──────────────────────────────────────
                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->getStyle('A4')->getFont()->setBold(true);

                // ── Row 9: PAR description ────────────────────────────────────
                $sheet->getStyle("A9:{$lastCol}9")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                ]);
                $sheet->getRowDimension(9)->setRowHeight(16);

                // ── Row 10: column headers ────────────────────────────────────
                $sheet->getStyle("A10:{$lastCol}10")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 9],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '666666']],
                    ],
                ]);
                $sheet->getRowDimension(10)->setRowHeight(52);

                // AI10 — yellow highlight (BNR marks eligible collateral column)
                $sheet->getStyle('AI10')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                ]);

                // ── Row 11: column numbers — amber (FFC000) ───────────────────
                $sheet->getStyle("A11:{$lastCol}11")->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']],
                    'font'      => ['bold' => true, 'size' => 8],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']],
                    ],
                ]);
                $sheet->getRowDimension(11)->setRowHeight(14);

                // Freeze below header rows
                $sheet->freezePane('A12');

                // ── Empty class placeholder ───────────────────────────────────
                if ($count === 0) {
                    $sheet->mergeCells("A12:{$lastCol}12");
                    $sheet->setCellValue('A12', 'No loans in this classification for the reporting period.');
                    $sheet->getStyle('A12')->applyFromArray([
                        'font'      => ['italic' => true, 'color' => ['rgb' => '888888']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    return;
                }

                // ── Data rows ─────────────────────────────────────────────────
                for ($r = 12; $r <= $dataEnd; $r++) {
                    $bg = $r % 2 === 0 ? $this->lightColor() : 'FFFFFF';

                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']],
                        ],
                    ]);

                    // Number format — money columns
                    foreach (['M', 'U', 'AG', 'AH', 'AI', 'AJ', 'AN', 'AO', 'AP'] as $col) {
                        $sheet->getStyle("{$col}{$r}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0');
                        $sheet->getStyle("{$col}{$r}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    // Annual interest rate as percentage
                    $sheet->getStyle("R{$r}")
                        ->getNumberFormat()
                        ->setFormatCode('0.00%');

                    // Provisioning rate as percentage
                    $sheet->getStyle("AM{$r}")
                        ->getNumberFormat()
                        ->setFormatCode('0%');

                    // Centre-align certain columns
                    foreach (['A', 'E', 'F', 'H', 'I', 'S', 'X', 'Y', 'AD', 'AE', 'AF', 'AK', 'AL'] as $col) {
                        $sheet->getStyle("{$col}{$r}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    $sheet->getRowDimension($r)->setRowHeight(16);
                }

                // ── Totals row ────────────────────────────────────────────────
                $totRow = $dataEnd + 1;
                $sheet->setCellValue("A{$totRow}", 'TOTALS');

                foreach ([
                    'U'  => 'Disbursed amount',
                    'AG' => 'Amount Repaid',
                    'AH' => 'Balance Outstanding',
                    'AI' => 'Eligible Collateral',
                    'AJ' => 'Net Amount Due',
                    'AN' => 'Provision Required',
                    'AP' => 'Additional Provisions',
                ] as $col => $label) {
                    $sheet->setCellValue("{$col}{$totRow}", "=SUM({$col}12:{$col}{$dataEnd})");
                    $sheet->getStyle("{$col}{$totRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');
                    $sheet->getStyle("{$col}{$totRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $sheet->getStyle("A{$totRow}:{$lastCol}{$totRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003D22']],
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003D22']],
                    ],
                ]);
                $sheet->getRowDimension($totRow)->setRowHeight(18);

                // Outline border around header + data + totals
                $sheet->getStyle("A10:{$lastCol}{$totRow}")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003D22']],
                    ],
                ]);
            },
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /** Build an info-block row, optionally placing a collateral type in col O */
    private function infoRow(array $data, int $padTo = 0, string $colO = ''): array
    {
        while (count($data) < $padTo) {
            $data[] = null;
        }
        if ($colO !== '' && count($data) <= 14) {
            while (count($data) < 14) {
                $data[] = null;
            }
            $data[] = $colO;
        }
        return $data;
    }

    /** Human-readable collateral label matching BNR reference list */
    private function collateralLabel(?string $type): string
    {
        return match ($type) {
            'cash_collateral'                                        => 'cash collateral',
            'government_or_central_bank'                            => 'Government or the Central Bank Bills and Bonds',
            'other_securities_offered_by_banks_operating_in_rwanda' => 'Other securities offered by the banks operating in Rwanda',
            'land_and_building'                                      => 'Land and Building',
            'movable_collaterals'                                    => 'movable collaterals.',
            default                                                  => '',
        };
    }

    /** BNR eligible collateral percentage per collateral type */
    private function eligiblePct(?string $type): float
    {
        return match ($type) {
            'cash_collateral'                                        => 1.00,
            'government_or_central_bank'                            => 1.00,
            'other_securities_offered_by_banks_operating_in_rwanda' => 1.00,
            'land_and_building'                                      => 0.60,
            'movable_collaterals'                                    => 0.40,
            default                                                  => 0.40,
        };
    }

    /** Convert installment frequency to number of days (BNR column X) */
    private function frequencyDays(string $freq): int
    {
        return match ($freq) {
            'daily'     => 1,
            'weekly'    => 7,
            'bi_weekly' => 14,
            'monthly'   => 30,
            'quarterly' => 90,
            default     => 30,
        };
    }

    /** Days since arrears start date */
    private function daysOverdue($loan): int
    {
        if (! $loan->date_when_arrears_start) {
            return 0;
        }
        return max(0, (int) Carbon::parse($loan->date_when_arrears_start)->diffInDays(now(), false));
    }

    /** Excel tab colour per loan class */
    private function tabColor(): string
    {
        return match ($this->classKey) {
            'normal'       => '27AE60',
            'watch'        => 'F39C12',
            'substandard'  => 'E67E22',
            'doubtful'     => 'E74C3C',
            'loss'         => '8E1A0E',
            'restructured' => '8E44AD',
            default        => '555555',
        };
    }

    /** Light alternate-row colour per loan class */
    private function lightColor(): string
    {
        return match ($this->classKey) {
            'normal'       => 'E8F5EE',
            'watch'        => 'FEF9E7',
            'substandard'  => 'FDEBD0',
            'doubtful'     => 'FDEDEC',
            'loss'         => 'F9EBEA',
            'restructured' => 'F5EEF8',
            default        => 'F5F5F5',
        };
    }
}
