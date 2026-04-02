<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Summary sheet — totals per loan class for the BNR report.
 */
class LoansBnrSummarySheet implements FromArray, WithTitle, WithStyles, ShouldAutoSize, WithEvents
{
    protected Collection $loans;
    protected string     $institutionName;
    protected string     $reportingDate;

    private const CLASS_COLORS = [
        'normal'       => '27AE60',
        'watch'        => 'F39C12',
        'substandard'  => 'E67E22',
        'doubtful'     => 'E74C3C',
        'loss'         => '8E1A0E',
        'restructured' => '8E44AD',
        'written_off'  => '555555',
    ];

    public function __construct(Collection $loans, string $institutionName, string $reportingDate)
    {
        $this->loans           = $loans;
        $this->institutionName = $institutionName;
        $this->reportingDate   = $reportingDate;
    }

    public function title(): string
    {
        return 'Summary';
    }

    public function array(): array
    {
        $rows = [];

        // Header row
        $rows[] = [
            'Loan Class',
            'No. of Loans',
            'Total Principal (RWF)',
            'Total Outstanding (RWF)',
            'Total Arrears (RWF)',
            'Total Collateral (RWF)',
            'Provision Rate (%)',
            'Total Provision (RWF)',
            'Net Exposure (RWF)',
        ];

        $grandLoans       = 0;
        $grandPrincipal   = 0;
        $grandOutstanding = 0;
        $grandArrears     = 0;
        $grandCollateral  = 0;
        $grandProvision   = 0;
        $grandExposure    = 0;

        foreach (LoansBnrExport::CLASSES as $classKey => $classLabel) {
            $group = $this->loans->filter(
                fn ($l) => ($l->loan_class ?? 'normal') === $classKey
            );

            $count       = $group->count();
            $principal   = $group->sum(fn ($l) => (float) $l->principal_amount);
            $outstanding = $group->sum(fn ($l) => max(0, (float) $l->total_amount - (float) ($l->amount_paid ?? 0)));
            $arrears     = $group->sum(fn ($l) => (float) ($l->arrears_amount ?? 0));
            $collateral  = $group->sum(fn ($l) => (float) ($l->collateral_value ?? 0));
            $provRate    = $this->getProvisionRate($classKey);
            $provision   = round($outstanding * ($provRate / 100), 2);
            $exposure    = round(max(0, $outstanding - $collateral), 2);

            $rows[] = [
                $classLabel,
                $count,
                number_format($principal, 2),
                number_format($outstanding, 2),
                number_format($arrears, 2),
                number_format($collateral, 2),
                $provRate . '%',
                number_format($provision, 2),
                number_format($exposure, 2),
            ];

            $grandLoans       += $count;
            $grandPrincipal   += $principal;
            $grandOutstanding += $outstanding;
            $grandArrears     += $arrears;
            $grandCollateral  += $collateral;
            $grandProvision   += $provision;
            $grandExposure    += $exposure;
        }

        // Grand total row
        $rows[] = [
            'GRAND TOTAL',
            $grandLoans,
            number_format($grandPrincipal, 2),
            number_format($grandOutstanding, 2),
            number_format($grandArrears, 2),
            number_format($grandCollateral, 2),
            '—',
            number_format($grandProvision, 2),
            number_format($grandExposure, 2),
        ];

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        // Header row (row 1 — will become row 5 after title insertion in events)
        // Styled in registerEvents after row insertion
        return [];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet      = $event->sheet->getDelegate();
                $classCount = count(LoansBnrExport::CLASSES);
                $dataRows   = $classCount + 1; // classes + header row

                // Set summary tab colour (dark green)
                $sheet->getTabColor()->setRGB('003D22');

                // Insert 3 title rows at the top
                $sheet->insertNewRowBefore(1, 3);

                // Row 1 — main title
                $sheet->mergeCells('A1:I1');
                $sheet->setCellValue('A1', strtoupper($this->institutionName) . ' — BNR CREDIT CLASSIFICATION REPORT');
                $sheet->getStyle('A1')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003D22']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                $sheet->getRowDimension(1)->setRowHeight(30);

                // Row 2 — reporting date
                $sheet->mergeCells('A2:I2');
                $sheet->setCellValue(
                    'A2',
                    'Reporting Date: ' . $this->reportingDate .
                    '     |     Total Loans: ' . $this->loans->count() .
                    '     |     Generated: ' . now()->format('d/m/Y H:i')
                );
                $sheet->getStyle('A2')->applyFromArray([
                    'font'      => ['italic' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '005C30']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(18);

                // Row 3 — blank separator
                $sheet->getRowDimension(3)->setRowHeight(6);
                $sheet->getStyle('A3:I3')->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F0F0F0']],
                ]);

                // Row 4 — column headers (was row 1 before insert)
                $sheet->getStyle('A4:I4')->applyFromArray([
                    'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1A5276']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical'   => Alignment::VERTICAL_CENTER,
                        'wrapText'   => true,
                    ],
                ]);
                $sheet->getRowDimension(4)->setRowHeight(36);
                $sheet->freezePane('A5');

                // Colour each class row
                $classKeys = array_keys(LoansBnrExport::CLASSES);
                foreach ($classKeys as $i => $classKey) {
                    $rowNum = 5 + $i; // data starts at row 5
                    $color  = self::CLASS_COLORS[$classKey] ?? '27AE60';

                    // Colour the class name cell
                    $sheet->getStyle("A{$rowNum}")->applyFromArray([
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
                        'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    ]);

                    // Light background for the rest of the row
                    $sheet->getStyle("B{$rowNum}:I{$rowNum}")->applyFromArray([
                        'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $i % 2 === 0 ? 'F7F9FC' : 'FFFFFF']],
                    ]);
                }

                // Grand total row
                $totalRow = 5 + $classCount;
                $sheet->getStyle("A{$totalRow}:I{$totalRow}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003D22']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                ]);
                $sheet->getRowDimension($totalRow)->setRowHeight(22);

                // Borders around entire table
                $lastRow = $totalRow;
                $sheet->getStyle("A4:I{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'CCCCCC']],
                        'outline'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003D22']],
                    ],
                ]);

                // Center numeric columns
                $sheet->getStyle("B5:I{$lastRow}")->applyFromArray([
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
                ]);
            },
        ];
    }

    private function getProvisionRate(string $classKey): float
    {
        return match ($classKey) {
            'normal'       => 1,
            'watch'        => 3,
            'substandard'  => 20,
            'doubtful'     => 50,
            'loss'         => 100,
            'written_off'  => 100,
            default        => 1,
        };
    }
}