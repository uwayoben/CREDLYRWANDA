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
 * Standard Loan Portfolio Report
 *
 * Location: app/Filament/Exports/LoansStandardExport.php
 *
 * One sheet, all loans, all sections:
 *   - Loan details       (number, customer, principal, dates)
 *   - Payment summary    (amount paid, principal paid, interest paid)
 *   - Remaining balance  (outstanding principal + interest)
 *   - Penalty & arrears  (penalty paid, arrears amount, days overdue)
 *   - Collateral info    (type, value, guarantee)
 *   - Loan officer & company
 *
 * Layout:
 *   Row 1  → Report title
 *   Row 2  → Generated date + total loans count
 *   Row 3  → blank separator
 *   Row 4  → Group header labels (merged spans)
 *   Row 5  → Column headers
 *   Row 6+ → Data rows
 *   Last   → TOTALS row
 */
class LoansStandardExport implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    protected Collection $loans;
    protected string     $institutionName;
    protected string     $reportingDate;

    public function __construct(
        Collection $loans,
        string     $institutionName = '',
        string     $reportingDate   = ''
    ) {
        $this->loans           = $loans;
        $this->institutionName = $institutionName ?: config('app.name', 'Institution');
        $this->reportingDate   = $reportingDate ?: now()->format('d/m/Y');
    }

    public function title(): string
    {
        return 'Loan Portfolio Report';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Group headers (row 4) — merged spans defined in registerEvents
    // ─────────────────────────────────────────────────────────────────────────

    private function groupHeaders(): array
    {
        return [
            'No.',                  // A  (standalone)
            'LOAN DETAILS',         // B  → spans B:I   (8 cols)
            '', '', '', '', '', '', '',
            'PAYMENT SUMMARY',      // J  → spans J:M   (4 cols)
            '', '', '',
            'OUTSTANDING',          // N  → spans N:O   (2 cols)
            '',
            'PENALTY & ARREARS',    // P  → spans P:S   (4 cols)
            '', '', '',
            'COLLATERAL',           // T  → spans T:V   (3 cols)
            '', '',
            'ADMINISTRATION',       // W  → spans W:Y   (3 cols)
            '', '',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Column headers (row 5) — 25 columns A–Y
    // ─────────────────────────────────────────────────────────────────────────

    private function columnHeaders(): array
    {
        return [
            // A  — row number
            'No.',

            // B–I — LOAN DETAILS
            'Loan Number',
            'Customer Name',
            'National ID',
            'Phone',
            'Loan Class',
            'Loan Status',
            'Disbursement Date',
            'Expected Completion',

            // J–M — PAYMENT SUMMARY
            'Principal Amount (RWF)',
            'Amount Paid (RWF)',
            'Principal Paid (RWF)',
            'Interest Paid (RWF)',

            // N–O — OUTSTANDING
            'Remaining Balance (RWF)',
            'Outstanding Interest (RWF)',

            // P–S — PENALTY & ARREARS
            'Penalty Rate (%)',
            'Penalty Paid (RWF)',
            'Arrears Amount (RWF)',
            'Days Overdue',

            // T–V — COLLATERAL
            'Collateral Type',
            'Collateral Value (RWF)',
            'Guarantee Type',

            // W–Y — ADMINISTRATION
            'Interest Rate (%)',
            'Interest Type',
            'Loan Officer',
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Build array
    // ─────────────────────────────────────────────────────────────────────────

    public function array(): array
    {
        $rows = [];

        // Rows 1–3 are inserted in registerEvents (title block)
        // Row 4 — group headers
        $rows[] = $this->groupHeaders();

        // Row 5 — column headers
        $rows[] = $this->columnHeaders();

        // Rows 6+ — data
        foreach ($this->loans as $i => $loan) {
            $rows[] = $this->mapLoan($loan, $i + 1);
        }

        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Map a single loan to a 25-column row
    // ─────────────────────────────────────────────────────────────────────────

    private function mapLoan($loan, int $no): array
    {
        $customer = $loan->customer;

        // ── Outstanding calculations ──────────────────────────────────────────
        $principal          = (float) $loan->principal_amount;
        $amountPaid         = (float) ($loan->amount_paid ?? 0);
        $principalPaid      = (float) ($loan->principal_paid ?? 0);
        $interestPaid       = (float) ($loan->interest_paid ?? 0);
        $totalInterest      = (float) ($loan->total_interest ?? 0);
        $penaltyPaid        = (float) ($loan->penalty_paid ?? 0);
        $arrearsAmount      = (float) ($loan->arrears_amount ?? 0);

        // Remaining balance (true outstanding including unpaid interest for declining)
        $remainingBalance = $loan->interest_type === 'declining'
            ? (float) $loan->remaining_balance + ($totalInterest - $interestPaid)
            : (float) $loan->remaining_balance;

        $outstandingInterest = max(0, $totalInterest - $interestPaid);

        // Days overdue
        $daysOverdue = 0;
        if ($loan->date_when_arrears_start && $remainingBalance > 0) {
            $daysOverdue = max(0, (int) Carbon::parse($loan->date_when_arrears_start)->diffInDays(now(), false));
        }

        return [
            // A  — row number
            $no,

            // B–I — LOAN DETAILS
            $loan->loan_number ?? '',
            $customer?->names ?? '',
            $customer?->national_id ?? '',
            $customer?->phone ?? '',
            ucfirst($loan->loan_class ?? '—'),
            ucwords(str_replace('_', ' ', $loan->loan_status ?? '—')),
            $loan->disbursement_date?->format('d/m/Y') ?? '',
            $loan->expected_completion_date?->format('d/m/Y') ?? '',

            // J–M — PAYMENT SUMMARY
            $principal,
            $amountPaid,
            $principalPaid,
            $interestPaid,

            // N–O — OUTSTANDING
            max(0, $remainingBalance),
            $outstandingInterest,

            // P–S — PENALTY & ARREARS
            (float) ($loan->penalty_rate ?? 0),
            $penaltyPaid,
            $arrearsAmount,
            $daysOverdue,

            // T–V — COLLATERAL
            $this->collateralLabel($loan->guarantee_collateral ?? ''),
            (float) ($loan->collateral_value ?? 0),
            $this->collateralLabel($loan->guarantee_collateral ?? ''),

            // W–Y — ADMINISTRATION
            (float) ($loan->interest_rate ?? 0),
            ucfirst($loan->interest_type ?? ''),
            $loan->createdBy?->name ?? '',
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
                $lastCol = 'Y';

                // Insert 3 title rows above the data
                $sheet->insertNewRowBefore(1, 3);

                // ── Row 1: Report title ───────────────────────────────────────
                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', strtoupper($this->institutionName) . ' — LOAN PORTFOLIO STANDARD REPORT');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003D22']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(28);

                // ── Row 2: Subtitle ───────────────────────────────────────────
                $sheet->mergeCells("A2:{$lastCol}2");
                $sheet->setCellValue(
                    'A2',
                    'Reporting Date: ' . $this->reportingDate .
                    '     |     Total Loans: ' . $count .
                    '     |     Generated: ' . now()->format('d/m/Y H:i')
                );
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '005C30']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(16);

                // ── Row 3: Blank separator ────────────────────────────────────
                $sheet->getStyle("A3:{$lastCol}3")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
                ]);
                $sheet->getRowDimension(3)->setRowHeight(6);

                // ── Row 4: Group headers (now shifted +3) ─────────────────────
                // Merges for each group
                $groups = [
                    'B4:I4' => ['label' => 'LOAN DETAILS',       'color' => '1A5276'],
                    'J4:M4' => ['label' => 'PAYMENT SUMMARY',     'color' => '1E8449'],
                    'N4:O4' => ['label' => 'OUTSTANDING',         'color' => '7D6608'],
                    'P4:S4' => ['label' => 'PENALTY & ARREARS',   'color' => '922B21'],
                    'T4:V4' => ['label' => 'COLLATERAL',          'color' => '6C3483'],
                    'W4:Y4' => ['label' => 'ADMINISTRATION',      'color' => '117A65'],
                ];

                // Style standalone No. cell
                $sheet->getStyle('A4')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003D22']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);

                foreach ($groups as $range => $meta) {
                    $sheet->mergeCells($range);
                    [$startCell] = explode(':', $range);
                    $sheet->setCellValue($startCell, $meta['label']);
                    $sheet->getStyle($range)->applyFromArray([
                        'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $meta['color']]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
                    ]);
                }
                $sheet->getRowDimension(4)->setRowHeight(18);

                // ── Row 5: Column headers ─────────────────────────────────────
                $sheet->getStyle("A5:{$lastCol}5")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1C2833']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '555555']]],
                ]);
                $sheet->getRowDimension(5)->setRowHeight(36);

                // Freeze below the 5-row header
                $sheet->freezePane('A6');

                // ── Empty state ───────────────────────────────────────────────
                if ($count === 0) {
                    $sheet->mergeCells("A6:{$lastCol}6");
                    $sheet->setCellValue('A6', 'No loans found for this reporting period.');
                    $sheet->getStyle('A6')->applyFromArray([
                        'font'      => ['italic' => true, 'color' => ['rgb' => '888888']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    return;
                }

                // ── Data rows (start at row 6) ────────────────────────────────
                $dataStart = 6;
                $dataEnd   = 5 + $count;

                // Group colour map for alternate shading
                $groupCols = [
                    'loan'     => ['B','C','D','E','F','G','H','I'],
                    'payment'  => ['J','K','L','M'],
                    'outstanding' => ['N','O'],
                    'penalty'  => ['P','Q','R','S'],
                    'collateral'  => ['T','U','V'],
                    'admin'    => ['W','X','Y'],
                ];

                $groupLightColors = [
                    'loan'        => ['even' => 'D6EAF8', 'odd' => 'EBF5FB'],
                    'payment'     => ['even' => 'D5F5E3', 'odd' => 'EAFAF1'],
                    'outstanding' => ['even' => 'FDFEFE', 'odd' => 'F2F3F4'],
                    'penalty'     => ['even' => 'FADBD8', 'odd' => 'FDEDEC'],
                    'collateral'  => ['even' => 'E8DAEF', 'odd' => 'F5EEF8'],
                    'admin'       => ['even' => 'D1F2EB', 'odd' => 'E8F8F5'],
                ];

                for ($r = $dataStart; $r <= $dataEnd; $r++) {
                    $even = $r % 2 === 0;

                    // Col A — No.
                    $sheet->getStyle("A{$r}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $even ? 'E5E7E9' : 'F2F3F4']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                        'font'      => ['bold' => true, 'size' => 8],
                    ]);

                    // Group shading
                    foreach ($groupCols as $group => $cols) {
                        $bg = $even
                            ? $groupLightColors[$group]['even']
                            : $groupLightColors[$group]['odd'];

                        foreach ($cols as $col) {
                            $sheet->getStyle("{$col}{$r}")->applyFromArray([
                                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']]],
                            ]);
                        }
                    }

                    // Number format — money columns
                    foreach (['J','K','L','M','N','O','Q','R','U'] as $col) {
                        $sheet->getStyle("{$col}{$r}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0');
                        $sheet->getStyle("{$col}{$r}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    // Percentage columns
                    foreach (['P','W'] as $col) {
                        $sheet->getStyle("{$col}{$r}")
                            ->getNumberFormat()
                            ->setFormatCode('0.00"%"');
                        $sheet->getStyle("{$col}{$r}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    // Days overdue — right align
                    $sheet->getStyle("S{$r}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_CENTER);

                    // Colour-code Loan Status column (G = col 7)
                    $status = $sheet->getCell("G{$r}")->getValue();
                    $statusColor = match (strtolower(str_replace(' ', '_', $status ?? ''))) {
                        'active'      => ['bg' => 'D5F5E3', 'fg' => '1E8449'],
                        'completed'   => ['bg' => 'D6EAF8', 'fg' => '1A5276'],
                        'disbursed'   => ['bg' => 'FEF9E7', 'fg' => '9A7D0A'],
                        'defaulted'   => ['bg' => 'FADBD8', 'fg' => '922B21'],
                        'written_off' => ['bg' => 'F2F3F4', 'fg' => '555555'],
                        'pending'     => ['bg' => 'F2F3F4', 'fg' => '717D7E'],
                        'approved'    => ['bg' => 'EBF5FB', 'fg' => '1A5276'],
                        default       => ['bg' => 'FFFFFF', 'fg' => '000000'],
                    };
                    $sheet->getStyle("G{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $statusColor['bg']]],
                        'font' => ['bold' => true, 'color' => ['rgb' => $statusColor['fg']]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);

                    // Colour-code Loan Class column (F = col 6)
                    $class = strtolower($sheet->getCell("F{$r}")->getValue() ?? '');
                    $classColor = match ($class) {
                        'normal'      => ['bg' => 'D5F5E3', 'fg' => '1E8449'],
                        'watch'       => ['bg' => 'FEF9E7', 'fg' => '9A7D0A'],
                        'substandard' => ['bg' => 'FDEBD0', 'fg' => 'CA6F1E'],
                        'doubtful'    => ['bg' => 'FADBD8', 'fg' => 'CB4335'],
                        'loss'        => ['bg' => 'F2D7D5', 'fg' => '7B241C'],
                        'restructured'=> ['bg' => 'E8DAEF', 'fg' => '6C3483'],
                        'written_off' => ['bg' => 'F2F3F4', 'fg' => '555555'],
                        default       => ['bg' => 'FFFFFF', 'fg' => '000000'],
                    };
                    $sheet->getStyle("F{$r}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $classColor['bg']]],
                        'font' => ['bold' => true, 'color' => ['rgb' => $classColor['fg']]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);

                    // Red text if days overdue > 0
                    $daysOverdue = (int) $sheet->getCell("S{$r}")->getValue();
                    if ($daysOverdue > 0) {
                        $sheet->getStyle("S{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => 'CB4335']],
                        ]);
                    }

                    $sheet->getRowDimension($r)->setRowHeight(15);
                }

                // ── TOTALS row ────────────────────────────────────────────────
                $totRow = $dataEnd + 1;
                $sheet->setCellValue("A{$totRow}", 'TOTALS');

                // SUM formulas for all numeric columns
                $sumCols = [
                    'J' => 'Principal Amount',
                    'K' => 'Amount Paid',
                    'L' => 'Principal Paid',
                    'M' => 'Interest Paid',
                    'N' => 'Remaining Balance',
                    'O' => 'Outstanding Interest',
                    'Q' => 'Penalty Paid',
                    'R' => 'Arrears Amount',
                    'U' => 'Collateral Value',
                ];

                foreach ($sumCols as $col => $label) {
                    $sheet->setCellValue("{$col}{$totRow}", "=SUM({$col}{$dataStart}:{$col}{$dataEnd})");
                    $sheet->getStyle("{$col}{$totRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');
                    $sheet->getStyle("{$col}{$totRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $sheet->getStyle("A{$totRow}:{$lastCol}{$totRow}")->applyFromArray([
                    'font'    => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
                    'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003D22']],
                    'borders' => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003D22']]],
                ]);
                $sheet->getRowDimension($totRow)->setRowHeight(20);

                // ── Column widths ─────────────────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(5);
                $sheet->getColumnDimension('B')->setWidth(16); // Loan Number
                $sheet->getColumnDimension('C')->setWidth(24); // Customer Name
                $sheet->getColumnDimension('D')->setWidth(18); // National ID
                $sheet->getColumnDimension('E')->setWidth(14); // Phone
                $sheet->getColumnDimension('F')->setWidth(14); // Loan Class
                $sheet->getColumnDimension('G')->setWidth(14); // Loan Status
                $sheet->getColumnDimension('H')->setWidth(14); // Disbursement Date
                $sheet->getColumnDimension('I')->setWidth(16); // Expected Completion
                foreach (['J','K','L','M','N','O','Q','R','U'] as $col) {
                    $sheet->getColumnDimension($col)->setWidth(18);
                }
                $sheet->getColumnDimension('P')->setWidth(12); // Penalty Rate
                $sheet->getColumnDimension('S')->setWidth(12); // Days Overdue
                $sheet->getColumnDimension('T')->setWidth(20); // Collateral Type
                $sheet->getColumnDimension('V')->setWidth(20); // Guarantee Type
                $sheet->getColumnDimension('W')->setWidth(12); // Interest Rate
                $sheet->getColumnDimension('X')->setWidth(14); // Interest Type
                $sheet->getColumnDimension('Y')->setWidth(22); // Loan Officer

                // ── Outline border around entire table ────────────────────────
                $sheet->getStyle("A4:{$lastCol}{$totRow}")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003D22']],
                    ],
                ]);

                // Tab colour
                $sheet->getTabColor()->setRGB('003D22');
            },
        ];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function collateralLabel(?string $type): string
    {
        return match ($type) {
            'cash_collateral'                                        => 'Cash Collateral',
            'government_or_central_bank'                            => 'Government / Central Bank',
            'other_securities_offered_by_banks_operating_in_rwanda' => 'Bank Securities (Rwanda)',
            'land_and_building'                                      => 'Land & Building',
            'movable_collaterals'                                    => 'Movable Assets',
            default                                                  => '—',
        };
    }
}
