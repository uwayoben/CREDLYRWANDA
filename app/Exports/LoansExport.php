<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Support\Facades\Auth;

class LoansExport extends ExcelExport implements WithStyles
{
    public function setUp(): void
    {
        parent::setUp();

        $user         = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;
        $companyId    = $user?->company_id;

        $this
            ->withFilename('loans-' . date('Y-m-d'))
            ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
            ->modifyQueryUsing(fn ($query) => $isSuperAdmin
                ? $query
                : $query->where('company_id', $companyId)
            )
            ->withColumns([
                Column::make('loan_number')
                    ->heading('Loan Number'),

                Column::make('customer.names')
                    ->heading('Customer Name'),

                Column::make('customer.national_id')
                    ->heading('National ID'),

                Column::make('customer.phone')
                    ->heading('Customer Phone'),

                Column::make('principal_amount')
                    ->heading('Principal Amount (RWF)'),

                Column::make('interest_rate')
                    ->heading('Interest Rate (%)')
                    ->formatStateUsing(fn ($state) => $state . '%'),

                Column::make('interest_type')
                    ->heading('Interest Type')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'declining' => 'Declining Balance',
                        'flat'      => 'Flat Rate',
                        default     => ucfirst($state ?? ''),
                    }),

                Column::make('number_of_installments')
                    ->heading('No. of Installments'),

                Column::make('installment_frequency')
                    ->heading('Frequency')
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state ?? ''))),

                Column::make('total_interest')
                    ->heading('Total Interest (RWF)'),

                Column::make('total_amount')
                    ->heading('Total Amount (RWF)'),

                Column::make('amount_paid')
                    ->heading('Amount Paid (RWF)'),

                Column::make('principal_paid')
                    ->heading('Principal Paid (RWF)'),

                Column::make('interest_paid')
                    ->heading('Interest Paid (RWF)'),

                Column::make('remaining_balance')
                    ->heading('Remaining Balance (RWF)'),

                Column::make('processing_fee')
                    ->heading('Processing Fee (RWF)'),

                Column::make('application_fee')
                    ->heading('Application Fee (RWF)'),

                Column::make('penalty_rate')
                    ->heading('Penalty Rate (%)')
                    ->formatStateUsing(fn ($state) => $state . '%'),

                Column::make('loan_status')
                    ->heading('Status')
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state ?? ''))),

                Column::make('loan_class')
                    ->heading('Loan Class')
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state ?? ''))),

                Column::make('disbursement_date')
                    ->heading('Disbursement Date')
                    ->formatStateUsing(fn ($state) => $state
                        ? Carbon::parse($state)->format('d/m/Y')
                        : ''
                    ),

                Column::make('first_payment_date')
                    ->heading('First Payment Date')
                    ->formatStateUsing(fn ($state) => $state
                        ? Carbon::parse($state)->format('d/m/Y')
                        : ''
                    ),

                Column::make('last_payment_date')
                    ->heading('Last Payment Date')
                    ->formatStateUsing(fn ($state) => $state
                        ? Carbon::parse($state)->format('d/m/Y')
                        : ''
                    ),

                Column::make('expected_completion_date')
                    ->heading('Expected Completion')
                    ->formatStateUsing(fn ($state) => $state
                        ? Carbon::parse($state)->format('d/m/Y')
                        : ''
                    ),

                Column::make('arrears_amount')
                    ->heading('Arrears Amount (RWF)'),

                Column::make('collateral_value')
                    ->heading('Collateral Value (RWF)'),

                Column::make('guarantee_collateral')
                    ->heading('Collateral Type')
                    ->formatStateUsing(fn ($state) => ucwords(str_replace('_', ' ', $state ?? ''))),

                Column::make('purpose')
                    ->heading('Loan Purpose'),

                Column::make('created_at')
                    ->heading('Created On')
                    ->formatStateUsing(fn ($state) => $state
                        ? Carbon::parse($state)->format('d/m/Y')
                        : ''
                    ),
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        $lastColumn = $sheet->getHighestColumn();
        $lastRow    = $sheet->getHighestRow();

        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'size'  => 11,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '14532d'], // dark green
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],

            "A2:{$lastColumn}{$lastRow}" => [
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ],
        ];
    }
}
