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
 * Sheet 9 — A1.9. Written off
 *
 * Location: app/Filament/Exports/BnrWrittenOffSheet.php
 *
 * Matches the BNR original exactly:
 *
 *   Row 1  → blank
 *   Row 2  → NDFSP Name + value
 *   Row 3  → institution name
 *   Row 4  → institution name + reporting date
 *   Row 5  → Report Name + label
 *   Row 6  → "Loans with 1 Year in Loss ( 720 days in arrears )"
 *   Row 7  → 24 column headers  (A–X)
 *   Row 8  → column numbers     (amber background FFC000)
 *   Row 9+ → loan data rows
 *   Last   → TOTALS row
 *
 * BNR formulas in data rows:
 *   S = Loan balance outstanding  = P − R
 *   U = Amount Written Off        = S − T
 *   X = Remaining Balance         = U − W
 */
class BnrWrittenOffSheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    protected Collection $loans;
    protected string     $institutionName;
    protected string     $reportingDate;

    // Column numbers label row (row 8) — 24 values for columns A–X
    private const COL_NUMBERS = [
        'Column 1',  'Column 2',  'Column 3',  'Column 4',  'Column 5',
        'Column 6',  'Column 7',  'Column 8',  'Column9',   'Column 10',
        'Column 11', 'Column 12', 'Column 13', 'Column 14', 'Column 15',
        'Column 16', 'Column 17', 'Column 18', 'Column 19', 'Column 20',
        'Column 21', 'Column 22', 'Column 23', 'Column 24',
    ];

    public function __construct(
        Collection $loans,
        string     $institutionName,
        string     $reportingDate
    ) {
        $this->loans           = $loans;
        $this->institutionName = $institutionName;
        $this->reportingDate   = $reportingDate;
    }

    public function title(): string
    {
        return 'A1.9. Written off';
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Build the sheet array
    // ─────────────────────────────────────────────────────────────────────────

    public function array(): array
    {
        $rows = [];

        // ── Rows 1–6: header block ────────────────────────────────────────────
        $rows[] = [null];                                                            // row 1 blank
        $rows[] = ['NDFSP Name', $this->institutionName];                            // row 2
        $rows[] = [$this->institutionName, null];                                    // row 3
        $rows[] = [$this->institutionName, null, $this->reportingDate];              // row 4
        $rows[] = ['Report Name ', 'Written Off Loans-Individuals ( 1 year in loss)']; // row 5
        $rows[] = ['Loans with 1 Year in Loss ( 720 days in arrears  )'];           // row 6

        // ── Row 7: 24 column headers ──────────────────────────────────────────
        $rows[] = [
            'Names of Borrowers',                                    // A
            'ID of the Borrower',                                    // B
            'Telephone number',                                      // C
            'Account Number',                                        // D
            'Gender',                                                // E
            'Age',                                                   // F
            'Relationship with the NDFSP',                           // G
            'Annual Interest Rate',                                  // H
            'Method of interest rate calculation (Flat/Declining)',  // I
            'Physical Guarantee',                                    // J
            "Borrower's District",                                   // K
            "Borrower's Sector",                                     // L
            "Borrower's Cell",                                       // M
            "Borrower's Village",                                    // N
            'Date of loan disbursement',                             // O
            'Amount of loan disbursed',                              // P
            'Maturity Date ',                                        // Q
            'Amount Repaid ',                                        // R
            'Loan balance outstanding',                              // S  (=P-R)
            'Security Savings',                                      // T
            'Amount Written Off',                                    // U  (=S-T)
            'Date of Write Off',                                     // V
            'Recoveries on the written off amount',                  // W
            'Remaining Balance to be Recovered',                     // X  (=U-W)
        ];

        // ── Row 8: column numbers (amber) ─────────────────────────────────────
        $rows[] = self::COL_NUMBERS;

        // ── Rows 9+: loan data (S, U, X set as formulas in registerEvents) ────
        foreach ($this->loans as $loan) {
            $customer   = $loan->customer;
            $principal  = (float) $loan->principal_amount;
            $amountPaid = (float) ($loan->amount_paid ?? 0);
            $annualRate = round((float) $loan->interest_rate / 100 * 12, 4);

            $rows[] = [
                $customer?->names ?? '',                                             // A
                $customer?->national_id ?? '',                                       // B
                $customer?->phone ?? '',                                             // C
                $loan->loan_number ?? '',                                            // D  Account Number
                ucfirst($customer?->gender ?? ''),                                   // E
                $customer?->date_of_birth                                            // F  Age
                    ? (int) now()->diffInYears($customer->date_of_birth)
                    : '',
                'Client',                                                            // G  Relationship
                $annualRate,                                                         // H  Annual Interest Rate
                $loan->interest_type === 'flat' ? 'Flat' : 'Declining',             // I  Method
                $loan->guarantee_collateral ?? '',                                   // J  Physical Guarantee
                $customer?->district ?? '',                                          // K
                $customer?->sector ?? '',                                            // L
                $customer?->cell ?? '',                                              // M
                $customer?->village ?? '',                                           // N
                $loan->disbursement_date?->format('d/m/Y') ?? '',                   // O
                $principal ?: null,                                                  // P  Amount disbursed
                $loan->expected_completion_date?->format('d/m/Y') ?? '',            // Q  Maturity Date
                $amountPaid ?: null,                                                 // R  Amount Repaid
                null,   // S  → formula =P-R  (set in registerEvents)
                null,   // T  Security Savings (manual entry)
                null,   // U  → formula =S-T  (set in registerEvents)
                $loan->completed_at?->format('d/m/Y') ?? '',                        // V  Date of Write Off
                null,   // W  Recoveries (manual entry)
                null,   // X  → formula =U-W  (set in registerEvents)
            ];
        }

        return $rows;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Styling & formulas
    // ─────────────────────────────────────────────────────────────────────────

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $count   = $this->loans->count();
                $lastCol = 'X';
                $dataEnd = 8 + $count; // rows 1–8 are header, data from row 9

                // ── Tab colour — dark grey ────────────────────────────────────
                $sheet->getTabColor()->setRGB('555555');

                // ── Column widths ─────────────────────────────────────────────
                $widths = [
                    'A' => 26, 'B' => 20, 'C' => 15, 'D' => 16, 'E' => 8,
                    'F' => 6,  'G' => 20, 'H' => 12, 'I' => 16, 'J' => 20,
                    'K' => 14, 'L' => 14, 'M' => 12, 'N' => 14, 'O' => 14,
                    'P' => 16, 'Q' => 14, 'R' => 16, 'S' => 18, 'T' => 14,
                    'U' => 18, 'V' => 14, 'W' => 20, 'X' => 22,
                ];
                foreach ($widths as $col => $w) {
                    $sheet->getColumnDimension($col)->setWidth($w);
                }

                // ── Rows 2–5: info block ──────────────────────────────────────
                $sheet->getStyle('A2:B5')->getFont()->setBold(true);

                // ── Row 6: description label — pale yellow (BNR) ─────────────
                $sheet->mergeCells("A6:{$lastCol}6");
                $sheet->getStyle("A6:{$lastCol}6")->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 10],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFCC']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(6)->setRowHeight(18);

                // ── Row 7: column headers ─────────────────────────────────────
                $sheet->getStyle("A7:{$lastCol}7")->applyFromArray([
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
                $sheet->getRowDimension(7)->setRowHeight(52);

                // ── Row 8: column numbers — amber (FFC000) ────────────────────
                $sheet->getStyle("A8:{$lastCol}8")->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']],
                    'font'      => ['bold' => true, 'size' => 8],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']],
                    ],
                ]);
                $sheet->getRowDimension(8)->setRowHeight(14);

                // Freeze below header rows
                $sheet->freezePane('A9');

                // ── Empty placeholder ─────────────────────────────────────────
                if ($count === 0) {
                    $sheet->mergeCells("A9:{$lastCol}9");
                    $sheet->setCellValue('A9', 'No written-off loans for this reporting period.');
                    $sheet->getStyle('A9')->applyFromArray([
                        'font'      => ['italic' => true, 'color' => ['rgb' => '888888']],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);
                    return;
                }

                // ── Data rows: formulas + formatting ──────────────────────────
                for ($r = 9; $r <= $dataEnd; $r++) {
                    // BNR required formulas
                    $sheet->setCellValue("S{$r}", "=P{$r}-R{$r}");  // Loan balance outstanding
                    $sheet->setCellValue("U{$r}", "=S{$r}-T{$r}");  // Amount Written Off
                    $sheet->setCellValue("X{$r}", "=U{$r}-W{$r}");  // Remaining Balance to be Recovered

                    // Alternate row shading
                    $bg = $r % 2 === 0 ? 'F2F3F4' : 'FFFFFF';
                    $sheet->getStyle("A{$r}:{$lastCol}{$r}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                        'borders'   => [
                            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'DDDDDD']],
                        ],
                    ]);

                    // Number format — money columns
                    foreach (['P', 'R', 'S', 'T', 'U', 'W', 'X'] as $col) {
                        $sheet->getStyle("{$col}{$r}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0');
                        $sheet->getStyle("{$col}{$r}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                    }

                    // Annual rate as percentage
                    $sheet->getStyle("H{$r}")
                        ->getNumberFormat()
                        ->setFormatCode('0.00%');

                    // Centre-align certain columns
                    foreach (['E', 'F', 'G', 'I'] as $col) {
                        $sheet->getStyle("{$col}{$r}")
                            ->getAlignment()
                            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
                    }

                    $sheet->getRowDimension($r)->setRowHeight(16);
                }

                // ── TOTALS row ────────────────────────────────────────────────
                $totRow = $dataEnd + 1;

                $sheet->setCellValue("A{$totRow}", 'TOTALS');

                foreach (['P', 'R', 'S', 'U', 'W', 'X'] as $col) {
                    $sheet->setCellValue("{$col}{$totRow}", "=SUM({$col}9:{$col}{$dataEnd})");
                    $sheet->getStyle("{$col}{$totRow}")
                        ->getNumberFormat()
                        ->setFormatCode('#,##0');
                    $sheet->getStyle("{$col}{$totRow}")
                        ->getAlignment()
                        ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
                }

                $sheet->getStyle("A{$totRow}:{$lastCol}{$totRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '333333']],
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '333333']],
                    ],
                ]);
                $sheet->getRowDimension($totRow)->setRowHeight(18);

                // ── Outline border around header + data + totals ──────────────
                $sheet->getStyle("A7:{$lastCol}{$totRow}")->applyFromArray([
                    'borders' => [
                        'outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '333333']],
                    ],
                ]);
            },
        ];
    }
}
