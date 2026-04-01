<?php

namespace App\Filament\Exports;

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
 * Sheet 2 — A1.2. FS (Financial Statements)
 *
 * Location: app/Filament/Exports/BnrFsSheet.php
 *
 * Matches the BNR original layout:
 *   Row 1 → Institution name + year
 *   Row 2 → Sector
 *   Row 3 → District + DENOMINATION header + 6 quarter-end date columns (D–I)
 *   Row 4+ → A. BALANCE SHEET, B. INCOME STATEMENT,
 *             C. OFF-BALANCE SHEET, D. SUPPLEMENTARY INFORMATION
 *
 * Only column I (current quarter) is populated from live loan data.
 * Columns D–H are left blank for manual entry of prior quarters.
 */
class BnrFsSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    protected Collection $loans;
    protected string     $institutionName;
    protected string     $reportingDate;
    protected string     $sector;
    protected string     $district;

    public function __construct(
        Collection $loans,
        string     $institutionName,
        string     $reportingDate,
        string     $sector   = '',
        string     $district = ''
    ) {
        $this->loans           = $loans;
        $this->institutionName = $institutionName;
        $this->reportingDate   = $reportingDate;
        $this->sector          = $sector;
        $this->district        = $district;
    }

    public function title(): string
    {
        return 'A1.2. FS';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Computed values from loan data
    // ─────────────────────────────────────────────────────────────────────────

    private function grossLoans(): float
    {
        return (float) $this->loans
            ->whereNotIn('loan_status', ['rejected', 'pending'])
            ->sum(fn ($l) => (float) $l->principal_amount);
    }

    private function provisions(): float
    {
        return (float) $this->loans->sum(function ($l) {
            $outstanding = max(0, (float) $l->total_amount - (float) ($l->amount_paid ?? 0));
            $rate = match ($l->loan_class ?? 'normal') {
                'watch'                  => 0.01,
                'substandard'            => 0.20,
                'doubtful'               => 0.50,
                'loss', 'written_off'    => 1.00,
                default                  => 0.00,
            };
            return round($outstanding * $rate, 2);
        });
    }

    private function netLoans(): float
    {
        return $this->grossLoans() - $this->provisions();
    }

    private function npls(): float
    {
        return (float) $this->loans
            ->whereIn('loan_class', ['substandard', 'doubtful', 'loss'])
            ->sum(fn ($l) => max(0, (float) $l->total_amount - (float) ($l->amount_paid ?? 0)));
    }

    private function interestIncome(): float
    {
        return (float) $this->loans->sum(fn ($l) => (float) ($l->interest_paid ?? 0));
    }

    private function outstandingByClass(string $class): float
    {
        return (float) $this->loans
            ->where('loan_class', $class)
            ->sum(fn ($l) => max(0, (float) $l->total_amount - (float) ($l->amount_paid ?? 0)));
    }

    private function outstandingByGender(string $gender): float
    {
        return (float) $this->loans
            ->whereIn('loan_status', ['active', 'disbursed', 'defaulted'])
            ->filter(fn ($l) => strtolower($l->customer?->gender ?? '') === $gender)
            ->sum(fn ($l) => max(0, (float) $l->total_amount - (float) ($l->amount_paid ?? 0)));
    }

    private function outstandingCountByGender(string $gender): int
    {
        return $this->loans
            ->whereIn('loan_status', ['active', 'disbursed', 'defaulted'])
            ->filter(fn ($l) => strtolower($l->customer?->gender ?? '') === $gender)
            ->count();
    }

    private function disbursedByGender(string $gender): float
    {
        return (float) $this->loans
            ->whereIn('loan_status', ['disbursed', 'active', 'completed', 'defaulted'])
            ->filter(fn ($l) => strtolower($l->customer?->gender ?? '') === $gender)
            ->sum(fn ($l) => (float) $l->principal_amount);
    }

    private function disbursedCountByGender(string $gender): int
    {
        return $this->loans
            ->whereIn('loan_status', ['disbursed', 'active', 'completed', 'defaulted'])
            ->filter(fn ($l) => strtolower($l->customer?->gender ?? '') === $gender)
            ->count();
    }

    /** Return null instead of 0 so empty cells stay blank */
    private function v(float $value): ?float
    {
        return $value == 0 ? null : round($value, 2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Build the 6 quarterly date headers (D–I)
    // The last column (I) = current reporting date; D–H = prior quarters blank
    // ─────────────────────────────────────────────────────────────────────────

    private function quarterDates(): array
    {
        // Parse the reporting date and build the 5 preceding quarter-end dates
        try {
            $end = \Carbon\Carbon::createFromFormat('d/m/Y', $this->reportingDate);
        } catch (\Exception $e) {
            $end = now()->endOfQuarter();
        }

        $dates = [];
        for ($i = 5; $i >= 1; $i--) {
            $dates[] = $end->copy()->subMonths($i * 3)->endOfQuarter()->format('d/m/Y');
        }
        $dates[] = $end->format('d/m/Y'); // current quarter — column I

        return $dates; // [D, E, F, G, H, I]
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Sheet data
    // ─────────────────────────────────────────────────────────────────────────

    public function array(): array
    {
        $g    = $this->grossLoans();
        $p    = $this->provisions();
        $net  = $this->netLoans();
        $npl  = $this->npls();
        $int  = $this->interestIncome();
        $q    = $this->quarterDates();      // [D, E, F, G, H, I]

        $menOut    = $this->outstandingByGender('male');
        $womenOut  = $this->outstandingByGender('female');
        $menCnt    = $this->outstandingCountByGender('male');
        $womenCnt  = $this->outstandingCountByGender('female');
        $totalCnt  = $menCnt + $womenCnt;

        $menDis    = $this->disbursedByGender('male');
        $womenDis  = $this->disbursedByGender('female');
        $menDisCnt = $this->disbursedCountByGender('male');
        $womDisCnt = $this->disbursedCountByGender('female');
        $totDisCnt = $menDisCnt + $womDisCnt;

        $normal      = $this->outstandingByClass('normal');
        $watch       = $this->outstandingByClass('watch');
        $substandard = $this->outstandingByClass('substandard');
        $doubtful    = $this->outstandingByClass('doubtful');
        $loss        = $this->outstandingByClass('loss');
        $restructured = $this->outstandingByClass('restructured');
        $writtenOff  = $this->outstandingByClass('written_off');

        // Each row: [A, B, C (denomination), D, E, F, G, H, I]
        // D–H = prior quarters (blank), I = current quarter value

        return [
            // ── Rows 1–3: header block ────────────────────────────────────────
            ['NAME OF THE NDFSP:', null, $this->institutionName, null, null, null, null, null, now()->year],
            ['SECTOR:   ',         null, $this->sector],
            ['DISTRICT:',          null, $this->district, 'DENOMINATION', $q[0], $q[1], $q[2], $q[3], $q[4], $q[5]],

            // ── A. BALANCE SHEET ──────────────────────────────────────────────
            [null, null, 'A.BALANCE SHEET'],
            [null, null, '1.Total Liquid Assets (2+3+4)',                          null, null, null, null, null, null],
            [null, null, '2.Cash in vault ',                                        null, null, null, null, null, null],
            [null, null, '3.Cash in bank and other FIs (Current account)',          null, null, null, null, null, null],
            [' ',  null, '4.Cash in bank and other FIs (Term deposit )',            null, null, null, null, null, null],
            [' ',  null, '5.Gross loans ',                                          null, null, null, null, null, $this->v($g)],
            [null, null, '6.Provisions ',                                           null, null, null, null, null, $this->v($p)],
            [null, null, '7.Net Loans (5-6)',                                       null, null, null, null, null, $this->v($net)],
            [null, null, '8.NPLs ',                                                 null, null, null, null, null, $this->v($npl)],
            [null, null, '9.Financial Instruments',                                 null, null, null, null, null, null],
            [null, null, 'Fixed Assets (PPE,Intangible, Investment properties, etc) Gross Amount', null, null, null, null, null, null],
            [null, null, 'Depreciation ',                                           null, null, null, null, null, null],
            [null, null, '10.Fixed Assets (net)',                                   null, null, null, null, null, null],
            [null, null, '11. Interest receivable',                                 null, null, null, null, null, null],
            [null, null, '12.Other Assets ',                                        null, null, null, null, null, null],
            [null, null, '13.Suspense Accounts',                                    null, null, null, null, null, null],
            [null, null, '14.Total Assets (1+7+9+10+11+12+13)=27',                 null, null, null, null, null, $this->v($net)],
            [null, null, '15.Total Liabilities (16+17+18+19)',                     null, null, null, null, null, null],
            [null, null, '16.Borrowings from other FIs and Non FIs (107)',          null, null, null, null, null, null],
            [null, null, ' 17.Cash collateral if any',                              null, null, null, null, null, 0],
            [null, null, '18. interest Payable',                                    null, null, null, null, null, null],
            [null, null, '19.Other liabilities (payables+suspense+other liabilities)', null, null, null, null, null, null],
            [' ',  null, '20.Total Equity ',                                        null, null, null, null, null, null],
            [null, null, '21.Subsidies  (for equipment or financing Equity)',        null, null, null, null, null, null],
            [null, null, '22.Revaluation surplus',                                   null, null, null, null, null, null],
            [null, null, '23.Other Equity',                                          null, null, null, null, null, null],
            [null, null, '24.Retained profits/Acc losses',                           null, null, null, null, null, 0],
            [null, null, '25.Profit/loss for the period',                            null, null, null, null, null, $this->v($int)],
            [null, null, '26.Paid Up Capital',                                       null, null, null, null, null, null],
            [null, null, '27.Total Equity & Liabilities (15+20)',                    null, null, null, null, null, null],

            // ── B. INCOME STATEMENT ───────────────────────────────────────────
            [null, null, 'B.INCOME STATEMENT'],
            [null, null, '28.Financial income (29+30+31+32+33)',                    null, null, null, null, null, $this->v($int)],
            [null, null, '29.Interest income on loan portfolio',                     null, null, null, null, null, $this->v($int)],
            [null, null, '30.Fees and commissions on loan portfolio',                null, null, null, null, null, null],
            [null, null, '31.Income on deposits in banks and other FIs',             null, null, null, null, null, null],
            [null, null, '32.Income on financial instruments',                       null, null, null, null, null, null],
            [null, null, '33.Other financial income',                                null, null, null, null, null, null],
            [null, null, '34.Recoveries on loans (Provisions back)',                 null, null, null, null, null, null],
            [null, null, '35.Recoveries on written off loans',                       null, null, null, null, null, null],
            [null, null, '36.Other operating income',                                null, null, null, null, null, null],
            [null, null, '37.Non-operating income',                                  null, null, null, null, null, null],
            [null, null, '38.Total incomes (28+34+35+36+37)',                        null, null, null, null, null, $this->v($int)],
            [null, null, '39.Financial expenses (40+41+42)',                         null, null, null, null, null, null],
            [null, null, '40.Interest on cash collateral',                           null, null, null, null, null, null],
            [null, null, '41.Interest on borrowings from FIs and Non FIs',           null, null, null, null, null, null],
            [null, null, '42.Bank charges, commissions and other financial expenses', null, null, null, null, null, null],
            [null, null, '43.Loan losses (Provisions)',                              null, null, null, null, null, $this->v($p)],
            [null, null, '44.Loan losses (Written off for the period)',               null, null, null, null, null, null],
            [null, null, '45.Personnel expenses (Gross amount)',                     null, null, null, null, null, null],
            [null, null, '46.Administrative expenses',                               null, null, null, null, null, null],
            [null, null, '47.Non-operating expenses',                                null, null, null, null, null, null],
            [null, null, '48.Total expenses (39+43+44+45+46+47)',                    null, null, null, null, null, $this->v($p)],
            [null, null, '49.Profit/Loss before donations (38-48)',                  null, null, null, null, null, $this->v($int - $p)],
            [null, null, '50.Income Tax',                                            null, null, null, null, null, null],
            [null, null, '51.Profit after Tax and before donations (49-50)',         null, null, null, null, null, $this->v($int - $p)],
            [null, null, '52.Donations (Financing Operating Expenses)',              null, null, null, null, null, null],
            [null, null, '53.Profit/Loss after Tax and Donations (51+52)',           null, null, null, null, null, $this->v($int - $p)],
            [null, null, 'Dividends',                                                null, null, null, null, null, null],
            [null, null, 'Net Profit After Dividends',                               null, null, null, null, null, $this->v($int - $p)],
            [null, null, '54.NPL Ratio (%)',     null, null, null, null, null, $g > 0 ? round(($npl / $g) * 100, 2) : null],
            [null, null, '55.Cost to Income',   null, null, null, null, null, null],
            [null, null, '56.ROA',              null, null, null, null, null, null],
            [null, null, '57.ROE',              null, null, null, null, null, null],

            // ── C. OFF-BALANCE SHEET ──────────────────────────────────────────
            [null, null, 'C.OFF-BALANCE SHEET (Written-Off Loans)'],
            [null, null, 'Written-Off Loans', null, null, null, null, null, $this->v($writtenOff)],

            // ── D. SUPPLEMENTARY INFORMATION ─────────────────────────────────
            [null, null, 'D.SUPPLEMENTARY INFORMATION'],

            // Number outstanding
            [null, '60.Number of Outstanding Loans', '61. Men',              null, null, null, null, null, $menCnt ?: null],
            [null, null,                              '62. Women',            null, null, null, null, null, $womenCnt ?: null],
            [null, null,                              '63. Group & Entities', null, null, null, null, null, null],
            [null, null,                              '64. Total (61+62+63)', null, null, null, null, null, $totalCnt ?: null],

            // Value outstanding by gender
            [null, '65.Value of Outstanding Loans by Gender', '66. Men',     null, null, null, null, null, $this->v($menOut)],
            [null, null,                                       '67. Women',   null, null, null, null, null, $this->v($womenOut)],
            [null, null,                                       '68. Group & Entities', null, null, null, null, null, null],
            [null, null,                                       '69. Total',   null, null, null, null, null, $this->v($menOut + $womenOut)],

            // Value outstanding by economic sector
            [null, '70.Value of Outstanding Loans by Economic Sector', '71. Agriculture, Livestock, Fishing', null, null, null, null, null, null],
            [null, null, '72. Public Works, Construction, Buildings',  null, null, null, null, null, null],
            [null, null, '73. Commerce, Restaurants, Hotels',          null, null, null, null, null, null],
            [null, null, '74. Transport, Warehouses, Communications',  null, null, null, null, null, null],
            [null, null, '75. Others',                                  null, null, null, null, null, $this->v($g)],
            [null, null, '76. Total (71+72+73+74+75)',                  null, null, null, null, null, $this->v($g)],

            // Loan classification
            [null, '77.Value of Outstanding Loans by Classification', '78. Current Loans - Normal (0% Prov.)', null, null, null, null, null, $this->v($normal)],
            [null, null, '79. Watch (1-89 days) 1% Prov.',             null, null, null, null, null, $this->v($watch)],
            [null, null, '80. Substandard (90-179 days) 20% Prov.',    null, null, null, null, null, $this->v($substandard)],
            [null, null, '81. Doubtful (180-359 days) 50% Prov.',      null, null, null, null, null, $this->v($doubtful)],
            [null, null, '82. Loss (360-719 days) 100% Prov.',         null, null, null, null, null, $this->v($loss)],
            [null, null, '83. Restructured',                             null, null, null, null, null, $this->v($restructured)],
            [null, null, '84. Total (78+79+80+81+82+83)',               null, null, null, null, null, $this->v($g)],

            // Number disbursed
            [null, '85.Number of Disbursed Loans (Quarter)', '86. Men',      null, null, null, null, null, $menDisCnt ?: null],
            [null, null,                                      '87. Women',    null, null, null, null, null, $womDisCnt ?: null],
            [null, null,                                      '88. Group & Entities', null, null, null, null, null, null],
            [null, null,                                      '89. Total',    null, null, null, null, null, $totDisCnt ?: null],

            // Value disbursed by gender
            [null, '90.Value of Disbursed Loans by Gender', '91. Men',       null, null, null, null, null, $this->v($menDis)],
            [null, null,                                     '92. Women',     null, null, null, null, null, $this->v($womenDis)],
            [null, null,                                     '93. Group & Entities', null, null, null, null, null, null],
            [null, null,                                     '94. Total',     null, null, null, null, null, $this->v($menDis + $womenDis)],

            // Value disbursed by sector
            [null, '95.Value of Disbursed Loans by Economic Sector', '96. Agriculture, Livestock, Fishing', null, null, null, null, null, null],
            [null, null, '97. Public Works, Construction, Buildings', null, null, null, null, null, null],
            [null, null, '98. Commerce, Restaurants, Hotels',         null, null, null, null, null, null],
            [null, null, '99. Transport, Warehouses, Communications', null, null, null, null, null, null],
            [null, null, '100. Others',                               null, null, null, null, null, $this->v($menDis + $womenDis)],
            [null, null, '101. Total (96+97+98+99+100)',              null, null, null, null, null, $this->v($menDis + $womenDis)],

            // NDFSP Borrowings
            [null, '102.NDFSP Borrowings', '103. Borrowing from Shareholders',   null, null, null, null, null, null],
            [null, null,                    '104. Borrowing from Related Parties', null, null, null, null, null, null],
            [null, null,                    '105. Borrowing from Banks/MFIs',      null, null, null, null, null, null],
            [null, null,                    '106. Borrowing from Other Sources',    null, null, null, null, null, null],
            [null, null,                    '107. Total (103+104+105+106)',          null, null, null, null, null, null],

            // Women entities
            [null, 'Financing Women Entities', 'Number of Disbursed Loans to WE (Quarter)',     null, null, null, null, null, null],
            [null, null,                        'Number of Outstanding Loans to WE (Cumulative)', null, null, null, null, null, null],
            [null, null,                        'Value of Disbursed Loans to WE (Quarter)',       null, null, null, null, null, $this->v($womenDis)],
            [null, null,                        'Value of Outstanding Loans to WE (Cumulative)',  null, null, null, null, null, $this->v($womenOut)],
            [null, null,                        'Number of Accounts with WE',                     null, null, null, null, null, $womDisCnt ?: null],

            // SMEs
            [null, 'Financing SMEs', 'Number of Disbursed Loans to SMEs (Quarter)', null, null, null, null, null, null],
            [null, null,             'Number of Outstanding Loans to SMEs',          null, null, null, null, null, null],
            [null, null,             'Value of Disbursed Loans to SMEs (Quarter)',   null, null, null, null, null, null],
            [null, null,             'Value of Outstanding Loans to SMEs',           null, null, null, null, null, null],

            // Youth
            [null, 'Financing Youth Entities', 'Number of Disbursed Loans to YE (Quarter)',   null, null, null, null, null, null],
            [null, null,                        'Number of Outstanding Loans to YE',            null, null, null, null, null, null],
            [null, null,                        'Value of Disbursed Loans to YE (Quarter)',     null, null, null, null, null, null],
            [null, null,                        'Value of Outstanding Loans to YE',             null, null, null, null, null, null],

            // New loans
            [null, 'New Loans', 'Number of Loans Applied For (Quarter)',  null, null, null, null, null, $totDisCnt ?: null],
            [null, null,        'Number of Loans Rejected (Quarter)',      null, null, null, null, null, null],
            [null, null,        'Amount of Loans Applied For (Quarter)',   null, null, null, null, null, $this->v($menDis + $womenDis)],
            [null, null,        'Amount of Loans Rejected (Quarter)',      null, null, null, null, null, null],

            // Staff
            [null, 'Number of NDFSP Staff',         'Men',           null, null, null, null, null, null],
            [null, null,                             'Women',         null, null, null, null, null, null],
            [null, null,                             'Total',         null, null, null, null, null, null],

            // Board Members
            [null, 'Number of NDFSP Board Members', 'Men',           null, null, null, null, null, null],
            [null, null,                             'Women',         null, null, null, null, null, null],
            [null, null,                             'Total',         null, null, null, null, null, null],

            // Shareholders
            [null, 'Number of NDFSP Shareholders',  'Men',           null, null, null, null, null, null],
            [null, null,                             'Women',         null, null, null, null, null, null],
            [null, null,                             'Legal Entities', null, null, null, null, null, null],
            [null, null,                             'Total',         null, null, null, null, null, null],
            [null, null,                             'Share Value',   null, null, null, null, null, null],
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
                $lastRow = $sheet->getHighestRow();

                // Tab colour
                $sheet->getTabColor()->setRGB('1A3C6E');

                // Column widths
                $sheet->getColumnDimension('A')->setWidth(4);
                $sheet->getColumnDimension('B')->setWidth(36);
                $sheet->getColumnDimension('C')->setWidth(52);
                foreach (['D', 'E', 'F', 'G', 'H', 'I'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(18);
                }

                // Row 1–2 bold
                $sheet->getStyle('A1:I2')->getFont()->setBold(true);

                // Row 3: date headers — BNR pale yellow (FFFFCC)
                $sheet->getStyle('C3:I3')->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFCC']],
                    'font'      => ['bold' => true],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']]],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(18);

                // Section headers and data rows from row 4
                $sections = [
                    'A.BALANCE SHEET',
                    'B.INCOME STATEMENT',
                    'C.OFF-BALANCE SHEET (Written-Off Loans)',
                    'D.SUPPLEMENTARY INFORMATION',
                ];

                for ($r = 4; $r <= $lastRow; $r++) {
                    $cVal = trim((string) $sheet->getCell("C{$r}")->getValue());
                    $bVal = trim((string) $sheet->getCell("B{$r}")->getValue());

                    // Section header rows
                    if (in_array($cVal, $sections)) {
                        $sheet->mergeCells("C{$r}:I{$r}");
                        $sheet->getStyle("A{$r}:I{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A3C6E']],
                        ]);
                        $sheet->getRowDimension($r)->setRowHeight(20);
                        continue;
                    }

                    // Sub-section group labels (col B has a value)
                    if (! empty($bVal)) {
                        $sheet->getStyle("B{$r}:C{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => '003D22']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'D4EDDA']],
                        ]);
                    }

                    // Alternate shading on denomination + value columns
                    $bg = $r % 2 === 0 ? 'F7F9FC' : 'FFFFFF';
                    $sheet->getStyle("C{$r}:I{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                    ]);

                    // Right-align and number-format value columns D–I
                    $sheet->getStyle("D{$r}:I{$r}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    $sheet->getStyle("D{$r}:I{$r}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');

                    $sheet->getRowDimension($r)->setRowHeight(15);
                }

                // Borders around the whole table
                $sheet->getStyle("A1:I{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'CCCCCC']],
                        'outline'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003D22']],
                    ],
                ]);

                // Freeze pane below the 3-row header
                $sheet->freezePane('D4');
            },
        ];
    }
}