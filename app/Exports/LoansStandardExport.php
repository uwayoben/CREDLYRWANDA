<?php

namespace App\Exports;

use App\Models\Loan;
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

class LoansStandardExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithStyles,
    ShouldAutoSize,
    WithTitle
{
    protected Collection $loans;

    public function __construct(Collection $loans)
    {
        $this->loans = $loans;
    }

    public function title(): string
    {
        return 'Loans Report';
    }

    public function collection(): Collection
    {
        return $this->loans;
    }

    public function headings(): array
    {
        return [
            '#',
            'Loan Number',
            'Customer Name',
            'National ID',
            'Phone',
            'Loan Status',
            'Loan Class',
            'Principal Amount (RWF)',
            'Interest Rate (%)',
            'Interest Type',
            'No. of Installments',
            'Installment Frequency',
            'Total Interest (RWF)',
            'Total Repayment (RWF)',
            'Processing Fee (RWF)',
            'Application Fee (RWF)',
            'Penalty Rate (%)',
            'Disbursement Date',
            'First Payment Date',
            'Last Payment Date',
            'Expected Completion Date',
            'Arrears Start Date',
            'Arrears Amount (RWF)',
            'Collateral Value (RWF)',
            'Collateral Type',
            'Collateral Details',
            'Guarantee / Collateral',
            'Loan Purpose',
            'Approved By',
            'Approved At',
            'Disbursed At',
            'Completed At',
            'Notes',
            'Created At',
        ];
    }

    public function map($loan): array
    {
        static $row = 0;
        $row++;

        return [
            $row,
            $loan->loan_number,
            $loan->customer?->names,
            $loan->customer?->national_id,
            $loan->customer?->phone,
            ucfirst($loan->loan_status),
            ucfirst($loan->loan_class ?? '—'),
            number_format($loan->principal_amount, 2),
            $loan->interest_rate,
            ucfirst($loan->interest_type),
            $loan->number_of_installments,
            ucfirst(str_replace('_', ' ', $loan->installment_frequency ?? '')),
            number_format($loan->total_interest, 2),
            number_format($loan->total_amount, 2),
            number_format($loan->processing_fee ?? 0, 2),
            number_format($loan->application_fee ?? 0, 2),
            $loan->penalty_rate ?? 0,
            $loan->disbursement_date?->format('d/m/Y'),
            $loan->first_payment_date?->format('d/m/Y'),
            $loan->last_payment_date?->format('d/m/Y'),
            $loan->expected_completion_date?->format('d/m/Y'),
            $loan->date_when_arrears_start?->format('d/m/Y'),
            number_format($loan->arrears_amount ?? 0, 2),
            number_format($loan->collateral_value ?? 0, 2),
            ucfirst(str_replace('_', ' ', $loan->guarantee_collateral ?? '—')),
            $loan->collateral_details ?? '—',
            $loan->guarantee_collateral ?? '—',
            $loan->purpose ?? '—',
            $loan->approvedBy?->name ?? '—',
            $loan->approved_at?->format('d/m/Y'),
            $loan->disbursed_at?->format('d/m/Y'),
            $loan->completed_at?->format('d/m/Y'),
            $loan->notes ?? '—',
            $loan->created_at?->format('d/m/Y H:i'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastCol = 'AH'; // Column 34
        $lastRow = $this->loans->count() + 1;

        // Header row style
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 11,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E3A5F'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);

        // Freeze header row
        $sheet->freezePane('A2');

        // Alternate row colors
        for ($i = 2; $i <= $lastRow; $i++) {
            $color = ($i % 2 === 0) ? 'F0F4FA' : 'FFFFFF';
            $sheet->getStyle("A{$i}:{$lastCol}{$i}")->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // Border around all data
        $sheet->getStyle("A1:{$lastCol}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color'       => ['rgb' => 'D0D7E3'],
                ],
            ],
        ]);

        // Row height for header
        $sheet->getRowDimension(1)->setRowHeight(30);

        return [];
    }
}