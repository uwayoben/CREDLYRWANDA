<?php

namespace App\Exports;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * A single BNR loan classification sheet (A1.3 – A1.8).
 *
 * Matches the official BNR template exactly:
 *   Rows 1–5  : institution info block
 *   Row 6     : blank
 *   Row 7     : collateral types (column O onwards — non-printing reference)
 *   Row 8     : blank
 *   Row 9     : PAR label + minimum provisioning rate
 *   Row 10    : Column headers (41 columns A–AP)
 *   Row 11    : Column numbers (amber background — FFFFC000)
 *   Row 12+   : Data rows
 */
class BnrLoanClassSheet implements FromArray, WithTitle, WithStyles, WithEvents
{
    protected Collection $loans;
    protected string     $classKey;
    protected array      $meta;      // ['sheet','par','rate','label']
    protected string     $institutionName;
    protected string     $reportingDate;

    // BNR collateral types in order (O2:O8 reference area)
    private const COLLATERAL_TYPES = [
        'cash collateral',
        'Government or the Central Bank Bills and Bonds ',
        'Other securities offered by the banks operating in Rwanda',
        'Land and Building',
        'movable collaterals.',
        'Biological assets',
        'Other assets ',
    ];

    // Column numbers row (row 11) — 41 columns A–AP
    private const COLUMN_NUMBERS = [
        'Column1','Column2','Column3','Column4','Column5','Column 6','Column 7',
        'Column 8','Column 9','Column10','Column11','Column12','Column 12',
        'Column13','Column14','Column15','Column16','Column17','Column18','Column19',
        'Column 20','Column 21','Column 22','Column 23','Column 24','Column 25',
        'Column 26','Column 27','Column 28','Column 29','Column 30','Column 31',
        'Column 32','Column 33','Column 34','Column 35','Column 36','Column 37',
        'Column 38','Column 39','Column 40','Column 41',
    ];

    public function __construct(
        Collection $loans,
        string $classKey,
        array $meta,
        string $institutionName,
        string $reportingDate
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

    // ── 41-column header definition ───────────────────────────────────────────

    private function columnHeaders(): array
    {
        return [
            'No',                                          // A
            'Names of Borrowers',                          // B
            'ID of the Borrower',                          // C
            'Telephone number',                            // D
            'Gender',                                      // E
            'Age',                                         // F
            'Relationship with the NDFSP ( Staff, Director, Shareholder…)', // G
            'Marital Status (Married/Single/Widow)',        // H
            'previous loans paid on time (Yes/No)',         // I
            'Purpose of the loan',                         // J
            'Branch name',                                 // K
            'Collateral Type',                             // L
            'Guarantee(Collateral) Ammount',               // M
            "Borrower's District",                         // N
            "Borrower's Sector",                           // O
            "Borrower's Cell",                             // P
            "Borrower's Village",                          // Q
            'Annual Interest Rate',                        // R
            'Method of interest rate calculation (Flat/Declining)', // S
            'Names of the Loan Officer',                   // T
            'Disbursed amount',                            // U
            'Date of loan disbursement',                   // V
            'Agreed Maturity Date',                        // W
            'Agreed Frequency of Repayment (Days)',        // X
            'Grace Period Accorded (Days)',                 // Y
            'Agreed Date of First Payment (Principal)',    // Z
            'Date of Last Payment (Principal)',            // AA
            'Date when Arrears Start',                     // AB
            'Cut Off Date (Report Date)',                  // AC
            'Total Number of Installments',               // AD
            'Round Number of Installments  paid',          // AE
            'Round Number of Installments outstanding',    // AF
            'Amount Repaid (Principal)',                    // AG
            'Balance Outstanding (Principal)',             // AH
            'Eligible Collateral provided ',               // AI
            'Net Amount due (Principal)',                  // AJ
            'Number of days overdue (Arrears) ',           // AK
            'Class',                                       // AL
            'Provisioning Rate (Regulation)',              // AM
            'Provision Required ',                         // AN
            'Previous Provisions',                         // AO
            'Additional Provisions',                       // AP
        ];
    }

    // ── Build array (rows 1–11 + data) ───────────────────────────────────────

    public function array(): array
    {
        $rows = [];
        $rate = $this->meta['rate'];
        $rateLabel = number_format($rate * 100, 0) . '%';
        $today = now()->format('d/m/Y');

        // Rows 1–5: institution info block
        // BNR format: col A = label, col C = value, col O = collateral reference list
        $rows[] = $this->padRow(['NDFSP Name', null, $this->institutionName], 14, self::COLLATERAL_TYPES[0] ?? '');  // row 1 → col O = collateral[0]
        $rows[] = $this->padRow([$this->institutionName], 14, self::COLLATERAL_TYPES[1] ?? ''); // row 2 col O
        $rows[] = $this->padRow([$this->institutionName, null, $this->reportingDate], 14, self::COLLATERAL_TYPES[2] ?? ''); // row 3
        $rows[] = $this->padRow(['Report Name ', null, $this->meta['label']], 14, self::COLLATERAL_TYPES[3] ?? '');
        $rows[] = $this->padRow([null], 14, self::COLLATERAL_TYPES[4] ?? ''); // row 5 blank + col O
        $rows[] = $this->padRow([null], 14, self::COLLATERAL_TYPES[5] ?? ''); // row 6
        $rows[] = $this->padRow([null], 14, self::COLLATERAL_TYPES[6] ?? ''); // row 7
        $rows[] = $this->padRow([null]);                                        // row 8 blank

        // Row 9: PAR + provisioning rate
        $row9 = array_fill(0, 42, null);
        $row9[0] = ' ';
        $row9[1] = $this->meta['par'];
        $row9[2] = 'Minimum provisioning rate required : ' . $rateLabel;
        $rows[] = $row9;

        // Row 10: Column headers (41 headers)
        $rows[] = $this->columnHeaders();

        // Row 11: Column numbers (amber row)
        $rows[] = self::COLUMN_NUMBERS;

        // Rows 12+: Data
        foreach ($this->loans as $i => $loan) {
            $rows[] = $this->mapLoan($loan, $i + 1, $today);
        }

        return $rows;
    }

    private function mapLoan($loan, int $no, string $today): array
    {
        $customer         = $loan->customer;
        $principal        = (float) $loan->principal_amount;
        $amountPaid       = (float) ($loan->principal_paid ?? 0);
        $outstanding      = max(0, $principal - $amountPaid);
        $collateralAmount = (float) ($loan->collateral_value ?? 0);
        $collateralType   = $this->collateralKey($loan->guarantee_collateral ?? '');
        $eligiblePct      = $this->eligibleCollateralPct($loan->guarantee_collateral ?? '');
        $eligible         = round($collateralAmount * $eligiblePct, 2);
        $netDue           = max(0, $outstanding - $eligible);
        $daysOverdue      = $this->daysOverdue($loan);
        $rate             = $this->meta['rate'];
        $provRequired     = $netDue > 0 ? round($netDue * $rate, 2) : 0;

        // Installments
        $totalInstallments = (int) $loan->number_of_installments;
        $paidInstallments  = $loan->installments ? $loan->installments->where('status', 'paid')->count() : 0;
        $outstanding_inst  = $totalInstallments - $paidInstallments;

        return [
            $no,                                                                     // A  No
            $customer?->names ?? '',                                                 // B  Names
            $customer?->national_id ?? '',                                           // C  ID
            $customer?->phone ?? '',                                                 // D  Phone
            ucfirst($customer?->gender ?? ''),                                       // E  Gender
            $customer?->date_of_birth ? now()->diffInYears($customer->date_of_birth) : '', // F Age
            $this->relationshipLabel($loan->relationship_with_ndfsp ?? ''),          // G  Relationship
            ucfirst($customer?->marital_status ?? ''),                               // H  Marital Status
            '',                                                                      // I  Previous loans on time
            $loan->purpose ?? '',                                                    // J  Purpose
            '',                                                                      // K  Branch name
            $collateralType,                                                         // L  Collateral Type
            $collateralAmount ?: null,                                               // M  Collateral Amount
            $customer?->district ?? '',                                              // N  District
            $customer?->sector ?? '',                                                // O  Sector
            $customer?->cell ?? '',                                                  // P  Cell
            $customer?->village ?? '',                                               // Q  Village
            (float) $loan->interest_rate / 100 * 12,                                // R  Annual Interest Rate
            $loan->interest_type === 'flat' ? 'Flat' : 'Declining',                 // S  Method
            $loan->createdBy?->name ?? '',                                           // T  Loan Officer
            $principal ?: null,                                                      // U  Disbursed Amount
            $loan->disbursement_date?->format('d/m/Y') ?? '',                       // V  Disbursement Date
            $loan->expected_completion_date?->format('d/m/Y') ?? '',                // W  Maturity Date
            $this->frequencyDays($loan->installment_frequency ?? 'monthly'),        // X  Frequency (days)
            0,                                                                       // Y  Grace Period
            $loan->first_payment_date?->format('d/m/Y') ?? '',                      // Z  First Payment Date
            $loan->last_payment_date?->format('d/m/Y') ?? '',                       // AA Last Payment Date
            $loan->date_when_arrears_start?->format('d/m/Y') ?? '',                 // AB Arrears Start
            $today,                                                                  // AC Cut Off Date
            $totalInstallments ?: null,                                              // AD Total Installments
            $paidInstallments ?: null,                                               // AE Paid Installments
            $outstanding_inst ?: null,                                               // AF Outstanding Installments
            $amountPaid ?: null,                                                     // AG Amount Repaid
            $outstanding ?: null,                                                    // AH Balance Outstanding
            $eligible ?: null,                                                       // AI Eligible Collateral
            $netDue ?: null,                                                         // AJ Net Amount Due
            $daysOverdue ?: null,                                                    // AK Days Overdue
            ucfirst($loan->loan_class ?? $this->classKey),                           // AL Class
            $rate,                                                                   // AM Prov Rate
            $provRequired ?: null,                                                   // AN Provision Required
            null,                                                                    // AO Previous Provisions
            $provRequired ?: null,                                                   // AP Additional Provisions
        ];
    }

    // ── Styles ────────────────────────────────────────────────────────────────

    public function styles(Worksheet $sheet): array
    {
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet    = $event->sheet->getDelegate();
                $count    = $this->loans->count();
                $lastCol  = 'AP';
                $lastRow  = max(12, $count + 11); // 11 header rows + data

                // Tab colour
                $sheet->getTabColor()->setRGB($this->tabColor());

                // ── Column widths ────────────────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(25);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(8);
                $sheet->getColumnDimension('F')->setWidth(6);
                $sheet->getColumnDimension('G')->setWidth(18);
                $sheet->getColumnDimension('H')->setWidth(15);
                $sheet->getColumnDimension('I')->setWidth(12);
                $sheet->getColumnDimension('J')->setWidth(20);
                $sheet->getColumnDimension('K')->setWidth(14);
                $sheet->getColumnDimension('L')->setWidth(18);
                $sheet->getColumnDimension('M')->setWidth(15);
                foreach (['N','O','P','Q'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(14);
                }
                $sheet->getColumnDimension('R')->setWidth(12);
                $sheet->getColumnDimension('S')->setWidth(14);
                $sheet->getColumnDimension('T')->setWidth(20);
                $sheet->getColumnDimension('U')->setWidth(16);
                $sheet->getColumnDimension('V')->setWidth(14);
                $sheet->getColumnDimension('W')->setWidth(14);
                $sheet->getColumnDimension('X')->setWidth(14);
                $sheet->getColumnDimension('Y')->setWidth(12);
                $sheet->getColumnDimension('Z')->setWidth(14);
                $sheet->getColumnDimension('AA')->setWidth(14);
                $sheet->getColumnDimension('AB')->setWidth(14);
                $sheet->getColumnDimension('AC')->setWidth(14);
                $sheet->getColumnDimension('AD')->setWidth(10);
                $sheet->getColumnDimension('AE')->setWidth(10);
                $sheet->getColumnDimension('AF')->setWidth(10);
                $sheet->getColumnDimension('AG')->setWidth(16);
                $sheet->getColumnDimension('AH')->setWidth(16);
                $sheet->getColumnDimension('AI')->setWidth(16);
                $sheet->getColumnDimension('AJ')->setWidth(16);
                $sheet->getColumnDimension('AK')->setWidth(12);
                $sheet->getColumnDimension('AL')->setWidth(14);
                $sheet->getColumnDimension('AM')->setWidth(12);
                $sheet->getColumnDimension('AN')->setWidth(16);
                $sheet->getColumnDimension('AO')->setWidth(16);
                $sheet->getColumnDimension('AP')->setWidth(16);

                // ── Rows 1–8: institution info block ─────────────────────────
                $sheet->getStyle("A1:AP8")->applyFromArray([
                    'font' => ['bold' => false, 'size' => 10],
                ]);
                $sheet->getStyle('A1')->getFont()->setBold(true);
                $sheet->getStyle('A4')->getFont()->setBold(true);
                $sheet->getStyle('A5')->getFont()->setBold(true); // report name label

                // ── Row 9: PAR description ────────────────────────────────────
                $sheet->getStyle("A9:{$lastCol}9")->applyFromArray([
                    'font' => ['bold' => true, 'size' => 10],
                ]);
                $sheet->getRowDimension(9)->setRowHeight(16);

                // ── Row 10: Column headers ────────────────────────────────────
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
                $sheet->getRowDimension(10)->setRowHeight(48);

                // Highlight AI10 (Eligible Collateral) in yellow per BNR original
                $sheet->getStyle('AI10')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFF00']],
                ]);

                // ── Row 11: Column numbers — amber background (FFFFC000) ──────
                $sheet->getStyle("A11:{$lastCol}11")->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']],
                    'font'      => ['bold' => true, 'size' => 8],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']]],
                ]);
                $sheet->getRowDimension(11)->setRowHeight(14);

                // Freeze pane at row 12 (after header rows)
                $sheet->freezePane('A12');

                if ($count === 0) {
                    // Empty class — show placeholder
                    $sheet->mergeCells("A12:{$lastCol}12");
                    $sheet->setCellValue('A12', 'No loans in this classification for the reporting period.');
                    $sheet->getStyle('A12')->applyFromArray([
                        'font'      => ['italic' => true, 'color' => ['rgb' => '888888']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    return;
                }

                // ── Data rows 12+ ─────────────────────────────────────────────
                for ($r = 12; $r <= $lastRow; $r++) {
                    // Alternate row shading
                    $bgColor = $r % 2 === 0 ? $this->lightColor() : 'FFFFFF';
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
                    ]);

                    // Number formatting for numeric columns
                    foreach (['M','U','AG','AH','AI','AJ','AN','AO','AP'] as $col) {
                        $sheet->getStyle("{$col}{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
                    }
                    // Annual interest rate as percentage
                    $sheet->getStyle("R{$r}")->getNumberFormat()->setFormatCode('0.00%');
                    // Provisioning rate
                    $sheet->getStyle("AM{$r}")->getNumberFormat()->setFormatCode('0%');

                    // Right-align numbers
                    $sheet->getStyle("M{$r}:M{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("U{$r}:U{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    foreach (['AG','AH','AI','AJ','AN','AO','AP'] as $col) {
                        $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    // Center-align certain columns
                    foreach (['A','E','F','H','I','R','S','X','Y','AD','AE','AF','AK','AL','AM'] as $col) {
                        $sheet->getStyle("{$col}{$r}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    $sheet->getRowDimension($r)->setRowHeight(16);
                }

                // ── Totals row ────────────────────────────────────────────────
                $totalsRow = $lastRow + 1;
                $sheet->setCellValue("A{$totalsRow}", 'TOTALS');
                $sheet->setCellValue("U{$totalsRow}", '=SUM(U12:U' . $lastRow . ')');
                $sheet->setCellValue("AG{$totalsRow}", '=SUM(AG12:AG' . $lastRow . ')');
                $sheet->setCellValue("AH{$totalsRow}", '=SUM(AH12:AH' . $lastRow . ')');
                $sheet->setCellValue("AI{$totalsRow}", '=SUM(AI12:AI' . $lastRow . ')');
                $sheet->setCellValue("AJ{$totalsRow}", '=SUM(AJ12:AJ' . $lastRow . ')');
                $sheet->setCellValue("AN{$totalsRow}", '=SUM(AN12:AN' . $lastRow . ')');
                $sheet->setCellValue("AP{$totalsRow}", '=SUM(AP12:AP' . $lastRow . ')');

                $sheet->getStyle("A{$totalsRow}:{$lastCol}{$totalsRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003D22']],
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003D22']]],
                ]);
                foreach (['U','AG','AH','AI','AJ','AN','AP'] as $col) {
                    $sheet->getStyle("{$col}{$totalsRow}")->getNumberFormat()->setFormatCode('#,##0.00');
                    $sheet->getStyle("{$col}{$totalsRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }
                $sheet->getRowDimension($totalsRow)->setRowHeight(18);

                // ── Outline border around whole data range ────────────────────
                $sheet->getStyle("A10:{$lastCol}{$totalsRow}")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003D22']],
                    ],
                ]);
            },
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function padRow(array $data, int $padUntil = 0, string $colO = ''): array
    {
        while (count($data) < $padUntil) {
            $data[] = null;
        }
        // col O is index 14
        if ($colO !== '' && count($data) <= 14) {
            while (count($data) < 14) $data[] = null;
            $data[] = $colO;
        }
        return $data;
    }

    private function collateralKey(?string $type): string
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

    private function eligibleCollateralPct(?string $type): float
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

    private function frequencyDays(string $frequency): int
    {
        return match ($frequency) {
            'daily'     => 1,
            'weekly'    => 7,
            'bi_weekly' => 14,
            'monthly'   => 30,
            'quarterly' => 90,
            default     => 30,
        };
    }

    private function daysOverdue($loan): int
    {
        if (! $loan->date_when_arrears_start) return 0;
        return max(0, (int) \Carbon\Carbon::parse($loan->date_when_arrears_start)->diffInDays(now(), false));
    }

    private function relationshipLabel(?string $val): string
    {
        return match ($val) {
            'staff'       => 'Staff',
            'director'    => 'Director',
            'shareholder' => 'Shareholder',
            default       => 'Client',
        };
    }

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