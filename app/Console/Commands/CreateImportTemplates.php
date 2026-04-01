<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class CreateImportTemplates extends Command
{
    protected $signature   = 'templates:create';
    protected $description = 'Create import template files for customers';

    public function handle(): void
    {
        $this->createCustomersTemplate();
        $this->info('✅ Templates created successfully!');
    }

    private function createCustomersTemplate(): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Customers');

        // ── Headers ───────────────────────────────────────────────
        $headers = [
            'A' => 'Full Names',
            'B' => 'National ID',
            'C' => 'Date of Birth',
            'D' => 'Gender',
            'E' => 'Marital Status',
            'F' => 'Phone Number',
            'G' => 'Email Address',
            'H' => 'Employment Status',
            'I' => 'Employer Name',
            'J' => 'Province',
            'K' => 'District',
            'L' => 'Sector',
            'M' => 'Cell',
            'N' => 'Village',
            'O' => 'Relationship with NDFSP',
            'P' => 'Spouse Name',
            'Q' => 'Spouse Phone',
            'R' => 'Spouse National ID',
            'S' => 'Marital Property Regime',
        ];

        foreach ($headers as $col => $heading) {
            $sheet->setCellValue("{$col}1", $heading);
        }

        // ── Style headers ─────────────────────────────────────────
        $lastCol = array_key_last($headers);
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray([
            'font' => [
                'bold'  => true,
                'size'  => 11,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1e40af'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
        ]);

        // ── Example row ───────────────────────────────────────────
        $example = [
            'A' => 'John Doe',
            'B' => '1199880012345678',
            'C' => '1990-01-15',
            'D' => 'male',
            'E' => 'married',
            'F' => '+250788123456',
            'G' => 'john@example.com',
            'H' => 'employed',
            'I' => 'ABC Company',
            'J' => 'Kigali City',
            'K' => 'Gasabo',
            'L' => 'Kimironko',
            'M' => 'Kibagabaga',
            'N' => 'Amarembo',
            'O' => 'Member',
            'P' => 'Jane Doe',
            'Q' => '+250788654321',
            'R' => '1199880098765432',
            'S' => 'Community of property',
        ];

        foreach ($example as $col => $value) {
            $sheet->setCellValue("{$col}2", $value);
        }

        // ── Style example row ─────────────────────────────────────
        $sheet->getStyle("A2:{$lastCol}2")->applyFromArray([
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'f0f9ff'],
            ],
            'font' => [
                'color' => ['rgb' => '64748b'],
                'italic' => true,
            ],
        ]);

        // ── Notes row ─────────────────────────────────────────────
        $sheet->setCellValue('A3', '--- Delete rows 2 and 3 before importing. Fill from row 2 onwards. ---');
        $sheet->mergeCells("A3:{$lastCol}3");
        $sheet->getStyle('A3')->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['rgb' => 'dc2626']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        // ── Dropdown validations ──────────────────────────────────
        // Gender
        $genderValidation = $sheet->getCell('D2')->getDataValidation();
        $genderValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $genderValidation->setFormula1('"male,female,other"');
        $genderValidation->setShowDropDown(false);
        $genderValidation->setSqref('D2:D10000');

        // Marital status
        $maritalValidation = $sheet->getCell('E2')->getDataValidation();
        $maritalValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $maritalValidation->setFormula1('"single,married,divorced,widowed"');
        $maritalValidation->setShowDropDown(false);
        $maritalValidation->setSqref('E2:E10000');

        // Employment status
        $employmentValidation = $sheet->getCell('H2')->getDataValidation();
        $employmentValidation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $employmentValidation->setFormula1('"employed,self_employed,unemployed,retired"');
        $employmentValidation->setShowDropDown(false);
        $employmentValidation->setSqref('H2:H10000');

        // ── Auto-size columns ─────────────────────────────────────
        foreach (array_keys($headers) as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // ── Row height ────────────────────────────────────────────
        $sheet->getRowDimension(1)->setRowHeight(25);

        // ── Save ──────────────────────────────────────────────────
        if (! is_dir(public_path('templates'))) {
            mkdir(public_path('templates'), 0755, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save(public_path('templates/customers-import-template.xlsx'));

        $this->info('   → customers-import-template.xlsx created at public/templates/');
    }
}
