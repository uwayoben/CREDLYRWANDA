<?php

namespace App\Exports;

use App\Models\Loan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * A single sheet for one BNR loan class inside LoansBnrExport.
 */
class LoansBnrClassSheet implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithTitle,
    WithEvents
{
    protected Collection $loans;
    protected string     $classKey;
    protected string     $classLabel;
    protected string     $institutionName;
    protected string     $reportingDate;

    // Tab colours per class
    private const TAB_COLORS = [
        'normal'       => '27AE60',
        'watch'        => 'F39C12',
        'substandard'  => 'E67E22',
        'doubtful'     => 'E74C3C',
        'loss'         => '8E1A0E',
        'restructured' => '8E44AD',
        'written_off'  => '555555',
    ];

    // Header background colours per class
    private const HEADER_COLORS = [
        'normal'       => ['dark' => '1A7A40', 'light' => '27AE60'],
        'watch'        => ['dark' => 'B7770D', 'light' => 'F39C12'],
        'substandard'  => ['dark' => 'A04000', 'light' => 'E67E22'],
        'doubtful'     => ['dark' => 'A93226', 'light' => 'E74C3C'],
        'loss'         => ['dark' => '5C0F08', 'light' => '8E1A0E'],
        'restructured' => ['dark' => '6C3483', 'light' => '8E44AD'],
        'written_off'  => ['dark' => '333333', 'light' => '555555'],
    ];

    public function __construct(
        Collection $loans,
        string $classKey,
        string $classLabel,
        string $institutionName,
        string $reportingDate
    ) {
        $this->loans           = $loans;
        $this->classKey        = $classKey;
        $this->classLabel      = $classLabel;
        $this->institutionName = $institutionName;
        $this->reportingDate   = $reportingDate;
    }

    public function title(): string
    {
        // Sheet tab name — max 31 chars for Excel
        return substr($this->classLabel, 0, 31);
    }

    public function collection(): Collection
    {
        return $this->loans;
    }

    public function headings(): array
    {
        return [
            // Row 1 — group labels (merged in registerEvents)
            [
                'No.',
                'BORROWER INFORMATION', '', '', '', '',
                'LOAN DETAILS',         '', '', '', '', '', '',
                'CLASSIFICATION',       '', '', '',
                'COLLATERAL',           '', '',
                'PROVISIONING',         '', '',
            ],
            // Row 2 — column headers
            [
                'No.',
                'Loan Number',
                'Borrower Name',
                'National ID / TIN',
                'Phone',
                'Gender',
                'Principal Amount (RWF)',
                'Outstanding Balance (RWF)',
                'Interest Rate (% p.m)',
                'Interest Type',
                'Disbursement Date',
                'Maturity Date',
                'No. of Installments',
                'Loan Classification',
                'Days in Arrears',
                'Arrears Amount (RWF)',
                'Loan Status',
                'Collateral Type',
                'Collateral Value (RWF)',
                'Collateral Details',
                'Provision Rate (%)',
                'Provision Amount (RWF)',
                'Net Exposure (RWF)',
            ],
        ];
    }

    public function map($loan): array
    {
        static $row = 0;
        $row++;

        $outstandingBalance = max(0, (float) $loan->total_amount - (float) ($loan->amount_paid ?? 0));
        $provisionRate      = $this->getProvisionRate($loan->loan_class);
        $provisionAmount    = round($outstandingBalance * ($provisionRate / 100), 2);
        $netExposure        = round(max(0, $outstandingBalance - (float) ($loan->collateral_value ?? 0)), 2);
        $daysInArrears      = $this->calculateDaysInArrears($loan);

        return [
            $row,
            $loan->loan_number,
            $loan->customer?->names,
            $loan->customer?->national_id,
            $loan->customer?->phone,
            ucfirst($loan->customer?->gender ?? '—'),
            number_format($loan->principal_amount, 2),
            number_format($outstandingBalance, 2),
            $loan->interest_rate,
            $loan->interest_type === 'flat' ? 'Flat Rate' : 'Declining Balance',
            $loan->disbursement_date?->format('d/m/Y'),
            $loan->last_payment_date?->format('d/m/Y'),
            $loan->number_of_installments,
            $this->classLabel,
            $daysInArrears,
            number_format($loan->arrears_amount ?? 0, 2),
            ucfirst($loan->loan_status),
            $this->collateralLabel($loan->guarantee_collateral),
            number_format($loan->collateral_value ?? 0, 2),
            $loan->collateral_details ?? '—',
            $provisionRate . '%',
            number_format($provisionAmount, 2),
            number_format($netExposure, 2),
        ];
    }

    // ── Styles ────────────────────────────────────────────────────────────────
    public function styles(Worksheet $sheet): array
    {
        $colors  = self::HEADER_COLORS[$this->classKey] ?? ['dark' => '006B3C', 'light' => '008F4C'];
        $lastCol = 'W';
        $count   = $this->loans->count();
        $lastRow = $count > 0 ? $count + 2 : 3;

        // Group header row (row 1)
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colors['dark']]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);

        // Column header row (row 2)
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 10],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colors['light']]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(36);
        $sheet->freezePane('A3');

        if ($count === 0) {
            // Empty sheet — show a "no data" message
            $sheet->mergeCells('A3:W3');
            $sheet->setCellValue('A3', 'No loans classified as "' . $this->classLabel . '" for this reporting period.');
            $sheet->getStyle('A3')->applyFromArray([
                'font'      => ['italic' => true, 'color' => ['rgb' => '888888']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            ]);
            return [];
        }

        // Alternate row shading
        for ($i = 3; $i <= $lastRow; $i++) {
            $bgColor = $i % 2 === 0 ? $this->lightenColor($this->classKey) : 'FFFFFF';
            $sheet->getStyle("A{$i}:{$lastCol}{$i}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgColor]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // Borders
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
                'outline'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $colors['dark']]],
            ],
        ]);

        return [];
    }

    // ── Events — title block + merges + tab color ─────────────────────────────
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet  = $event->sheet->getDelegate();
                $colors = self::HEADER_COLORS[$this->classKey] ?? ['dark' => '006B3C', 'light' => '008F4C'];
                $count  = $this->loans->count();

                // Set tab colour
                $sheet->getTabColor()->setRGB(self::TAB_COLORS[$this->classKey] ?? '006B3C');

                // Insert 2 title rows at the top
                $sheet->insertNewRowBefore(1, 2);

                // Row 1 — report title
                $sheet->mergeCells('A1:W1');
                $sheet->setCellValue(
                    'A1',
                    strtoupper($this->institutionName) .
                    ' — BNR CREDIT REPORT | ' .
                    strtoupper($this->classLabel) .
                    ' LOANS'
                );
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 13, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003D22']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(26);

                // Row 2 — reporting date + count
                $sheet->mergeCells('A2:W2');
                $sheet->setCellValue(
                    'A2',
                    'Reporting Date: ' . $this->reportingDate .
                    '     |     Total Loans: ' . $count .
                    '     |     Generated: ' . now()->format('d/m/Y H:i')
                );
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $colors['dark']]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(18);

                // Merge group header cells (now on row 3)
                $sheet->mergeCells('B3:F3'); // Borrower Information
                $sheet->mergeCells('G3:M3'); // Loan Details
                $sheet->mergeCells('N3:Q3'); // Classification
                $sheet->mergeCells('R3:T3'); // Collateral
                $sheet->mergeCells('U3:W3'); // Provisioning

                // Freeze from row 5 (2 title + 2 header rows)
                $sheet->freezePane('A5');

                // Totals row at the bottom
                if ($count > 0) {
                    $totalsRow = $count + 4 + 1; // +4 for inserted rows, +1 for next row
                    $sheet->setCellValue("A{$totalsRow}", 'TOTALS');
                    $sheet->setCellValue("G{$totalsRow}", $this->loans->sum(fn ($l) => (float) $l->principal_amount));
                    $sheet->setCellValue("H{$totalsRow}", $this->loans->sum(fn ($l) => max(0, (float) $l->total_amount - (float) ($l->amount_paid ?? 0))));
                    $sheet->setCellValue("P{$totalsRow}", $this->loans->sum(fn ($l) => (float) ($l->arrears_amount ?? 0)));
                    $sheet->setCellValue("S{$totalsRow}", $this->loans->sum(fn ($l) => (float) ($l->collateral_value ?? 0)));

                    // Provision amount total (column V)
                    $totalProvision = $this->loans->sum(function ($l) {
                        $balance = max(0, (float) $l->total_amount - (float) ($l->amount_paid ?? 0));
                        return round($balance * ($this->getProvisionRate($l->loan_class) / 100), 2);
                    });
                    $sheet->setCellValue("V{$totalsRow}", $totalProvision);

                    $sheet->getStyle("A{$totalsRow}:W{$totalsRow}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003D22']],
                    ]);

                    // Format number cells in totals row
                    foreach (['G', 'H', 'P', 'S', 'V'] as $col) {
                        $sheet->getStyle("{$col}{$totalsRow}")
                            ->getNumberFormat()
                            ->setFormatCode('#,##0.00');
                    }
                }
            },
        ];
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function lightenColor(string $classKey): string
    {
        return match ($classKey) {
            'normal'       => 'E8F5EE',
            'watch'        => 'FEF9E7',
            'substandard'  => 'FDEBD0',
            'doubtful'     => 'FDEDEC',
            'loss'         => 'F9EBEA',
            'restructured' => 'F5EEF8',
            'written_off'  => 'F2F3F4',
            default        => 'F5F5F5',
        };
    }

    private function getProvisionRate(?string $class): float
    {
        return match ($class) {
            'normal'       => 1,
            'watch'        => 3,
            'substandard'  => 20,
            'doubtful'     => 50,
            'loss'         => 100,
            'written_off'  => 100,
            default        => 1,
        };
    }

    private function collateralLabel(?string $type): string
    {
        return match ($type) {
            'cash_collateral'                                        => 'Cash Collateral',
            'government_or_central_bank'                            => 'Government / Central Bank',
            'other_securities_offered_by_banks_operating_in_rwanda' => 'Bank Securities (Rwanda)',
            'land_and_building'                                      => 'Land & Building',
            'movable_collaterals'                                    => 'Movable Assets',
            default                                                  => 'None',
        };
    }

    private function calculateDaysInArrears($loan): int
    {
        if (! $loan->date_when_arrears_start) return 0;
        if (! in_array($loan->loan_status, ['active', 'defaulted', 'arrears'])) return 0;
        return max(0, (int) Carbon::parse($loan->date_when_arrears_start)->diffInDays(now(), false));
    }
}