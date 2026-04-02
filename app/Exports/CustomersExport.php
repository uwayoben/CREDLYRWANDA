<?php

namespace App\Exports;

use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class CustomersExport extends ExcelExport implements WithStyles
{
    public function setUp(): void
    {
         parent::setUp();
           $user      = \Illuminate\Support\Facades\Auth::user();
    $isSuperAdmin = $user?->is_super_admin ?? false;
    $companyId = $user?->company_id;
        $this
              ->withFilename('customers-' . date('Y-m-d'))
        ->withWriterType(\Maatwebsite\Excel\Excel::XLSX)
        ->modifyQueryUsing(fn ($query) => $isSuperAdmin
            ? $query
            : $query->where('company_id', $companyId)
        )
            ->withColumns([
                Column::make('names')
                    ->heading('Full Names'),

                Column::make('national_id')
                    ->heading('National ID'),

                Column::make('date_of_birth')
                    ->heading('Date of Birth')
                    ->formatStateUsing(fn ($state) => $state
                        ? Carbon::parse($state)->format('d/m/Y')
                        : ''
                    ),

                Column::make('gender')
                    ->heading('Gender')
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? '')),

                Column::make('marital_status')
                    ->heading('Marital Status')
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? '')),

                Column::make('phone')
                    ->heading('Phone Number'),

                Column::make('email')
                    ->heading('Email Address'),

                Column::make('employment_status')
                    ->heading('Employment Status')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'self_employed' => 'Self Employed',
                        default         => ucfirst($state ?? ''),
                    }),

                Column::make('employer_name')
                    ->heading('Employer Name'),

                Column::make('province')
                    ->heading('Province'),

                Column::make('district')
                    ->heading('District'),

                Column::make('sector')
                    ->heading('Sector'),

                Column::make('cell')
                    ->heading('Cell'),

                Column::make('village')
                    ->heading('Village'),

                Column::make('relationship_with_ndfsp')
                    ->heading('Relationship with NDFSP'),

                Column::make('spouse_name')
                    ->heading('Spouse Name'),

                Column::make('spouse_phone')
                    ->heading('Spouse Phone'),

                Column::make('spouse_id_number')
                    ->heading('Spouse National ID'),

                Column::make('marital_property_regime')
                    ->heading('Marital Property Regime'),

                Column::make('is_active')
                    ->heading('Status')
                    ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive'),

                Column::make('created_at')
                    ->heading('Registered On')
                    ->formatStateUsing(fn ($state) => $state
                        ? Carbon::parse($state)->format('d/m/Y')
                        : ''
                    ),
            ]);
    }

    public function styles(Worksheet $sheet): array
    {
        // Get the last column letter dynamically
        $lastColumn = $sheet->getHighestColumn();
        $lastRow    = $sheet->getHighestRow();

        return [
            // ── Row 1: Header row ──────────────────────────────
            1 => [
                'font' => [
                    'bold'  => true,
                    'size'  => 11,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '1e40af'], // dark blue
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],

            // ── All data rows: wrap text and center ────────────
            "A2:{$lastColumn}{$lastRow}" => [
                'alignment' => [
                    'vertical'  => Alignment::VERTICAL_CENTER,
                    'wrapText'  => true,
                ],
            ],
        ];
    }
}
