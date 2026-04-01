<?php

namespace App\Filament\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Sheet 1 — A1.1 Explanatory Note
 *
 * Location: app/Filament/Exports/BnrExplanatorySheet.php
 *
 * Mirrors the BNR original exactly:
 *   Col B → DENOMINATION (field name)
 *   Col C → Explanatory Notes (description)
 *   Section headers have a dark background
 *   Alternate rows have light shading
 */
class BnrExplanatorySheet implements FromArray, WithTitle, ShouldAutoSize, WithEvents
{
    public function title(): string
    {
        return 'A1.1  Explanatory Note ';
    }

    public function array(): array
    {
        return [
            // Row 1 — blank
            [null, null, null],

            // Row 2 — column headers
            [null, 'DENOMINATION', 'Explanatory Notes'],

            // ── A. BALANCE SHEET ──────────────────────────────────────────────
            [null, 'A.BALANCE SHEET', null],
            [null, '1.Total Liquid Assets (2+3+4)', null],
            [null, '2.Cash in vault ', 'In this section, Accountant record physical cash (Coins or Notes) held on the institution\'s premises as at reporting date.'],
            [null, '3.Cash in bank and other FIs (Current account)', 'Report the total balances at all banks or financial institutions including Mobile Money, used for transactional liquidity.'],
            [' ',  '4.Cash in bank and other FIs (Term deposit )', 'Cash placed in fixed‑term accounts earning interest but less liquid.'],
            [' ',  '5.Gross loans ', 'Do not edit anything here. The formula will take the total outstanding loan balance issued to clients, as per loan classification in line (77–84.)'],
            [null, '6.Provisions ', 'Add the provisions computed in allowances established against expected credit losses in compliance with Article 39 of REGULATION No 65/04/2023 OF 25/04/2023.'],
            [null, '7.Net Loans (5-6)', null],
            [null, '8.NPLs ', 'Add all non performing loans outstanding as at reporting date (substandard, Doubtful and loss) refer to Article 3: Paragraph u'],
            [null, '9.Financial Instruments', 'Investments in securities or other financial assets permitted under regulations.'],
            [null, 'Fixed Assets (PPE,Intangible, Investment properties, etc) Gross Amount', 'Capital expenditure on property, plant & equipment, intangible and investment assets. The accountant records the actual cost incurred to acquire assets as per IAS 16.'],
            [null, 'Depreciation ', 'Add the Depreciation for the quarter to the previously reported accumulated depreciation.'],
            [null, '10.Fixed Assets (net)', null],
            [null, '11. Interest receivable', 'Accrued interest incurred but not yet paid by clients as at the end of quarter.'],
            [null, '12.Other Assets ', 'Assets such as prepayments, accrued interest income, or any other items not classified elsewhere.'],
            [null, '13.Suspense Accounts', 'Temporary ledger accounts holding unresolved or pending transaction entries.'],
            [null, '14.Total Assets (1+7+9+10+11+12)=25', null],
            [null, '15.Total Liabilities (15+16+20)', null],
            [null, '16.Borrowings from other FIs and Non FIs', 'Outstanding Debt obligations owed to the Bank and other creditors payable in the period exceeding 1 year.'],
            [null, ' 17.Cash collateral if any', 'Cash pledged as collateral for third-party obligations or regulatory requirements.'],
            [null, '18. interest Payable', 'Accrued interest payable to the lender at the end of quarter as per Loan amortization.'],
            [null, '19.Other liabilities (payables+suspense+other liabilities)', 'Includes trade payables, payroll liabilities, interest payable, suspense liability accounts, accruals, and miscellaneous obligations.'],
            [' ',  '20.Total Equity ', null],
            [null, '21.Subsidies  (for equipment or financing Equity)', 'Grants or donor‑funded investments provided to support capital or operational capacity. If any.'],
            [null, '22.Revaluation surplus', 'Record all Unrealized gains from reappraisal of assets recognized under accounting standards.'],
            [null, '23.Other Equity', 'Any other equity categories such as statutory reserves or specific retained components.'],
            [null, '24.Retained profits/Acc losses', 'Record the opening accumulated P/L as at the start of quarter.'],
            [null, '25.Profit/loss for the period', 'Link to profit in P/L account.'],

            // ── B. INCOME STATEMENT ───────────────────────────────────────────
            [null, 'B.INCOME STATEMENT', null],
            [null, '26.Financial income', 'All income generated from financial activities including interest and fees on loans.'],
            [null, '27.Interest income on loan portfolio', 'Interest earned on loans extended to clients during the reporting period.'],
            [null, '28.Fees and commissions on loan portfolio', 'Fees charged on loan origination, processing, and administration.'],
            [null, '29.Income on deposits in banks and other FIs', 'Interest or returns earned on deposits placed at banks or other financial institutions.'],
            [null, '30.Income on financial instruments', 'Returns from investment in financial instruments such as government securities.'],
            [null, '31.Other financial income', 'Any other income of a financial nature not categorized above.'],
            [null, '32.Recoveries on loans (Provisions back)', 'Amounts previously provisioned that have been recovered and reversed.'],
            [null, '33.Recoveries on written off loans', 'Cash recovered on loans previously written off from the books.'],
            [null, '34.Other operating income', 'Non-financial income from operations such as rental income or service charges.'],
            [null, '35.Non-operating income', 'Income not related to primary business operations.'],
            [null, '36.Total incomes', 'Sum of all income lines above.'],
            [null, '37.Financial expenses', 'Total costs incurred in raising and servicing financial resources.'],
            [null, '38.Interest on cash collateral', 'Interest paid on cash pledged as collateral by third parties.'],
            [null, '39.Interest on borrowings from FIs and Non FIs', 'Interest paid on funds borrowed from banks or other financial institutions.'],
            [null, '40.Bank charges, commissions and other financial expenses', 'Transaction costs, bank fees, and other financing charges.'],
            [null, '41.Loan losses (Provisions)', 'Amount set aside as provisions for expected loan losses during the period.'],
            [null, '42.Loan losses (Written off for the period)', 'Loans formally removed from the books as uncollectible during the period.'],
            [null, '43.Personnel expenses (Gross amount)', 'Total staff costs including salaries, benefits, and social contributions.'],
            [null, '44.Administrative expenses', 'Office, operational, and administrative overhead costs.'],
            [null, '45.Non-operating expenses', 'Costs not related to core business operations.'],
            [null, '46.Total expenses', 'Sum of all expense lines above.'],
            [null, '47.Profit/Loss before donations', 'Net result before accounting for any donor subsidies.'],

            // ── C. SUPPLEMENTARY INFORMATION ─────────────────────────────────
            [null, 'C.SUPPLEMENTARY INFORMATION', null],
            [null, '48.Number of outstanding loans by gender', 'Breakdown of active loan accounts by borrower gender.'],
            [null, '49.Value of outstanding loans by gender', 'Total outstanding principal balances classified by borrower gender.'],
            [null, '50.Value of outstanding loans by economic sector', 'Portfolio distribution across agriculture, trade, transport, and other sectors.'],
            [null, '51.Loan classification summary', 'Summary of loans by BNR classification category (Normal, Watch, Substandard, Doubtful, Loss, Restructured).'],
            [null, '52.Number of disbursed loans by gender', 'Count of loans disbursed during the period, broken down by gender.'],
            [null, '53.Value of disbursed loans by gender', 'Total amounts disbursed during the period by borrower gender.'],
            [null, '54.Value of disbursed loans by economic sector', 'Disbursements classified by productive sector.'],
            [null, '55.NDFSP Borrowings', 'Funds borrowed by the NDFSP from shareholders, banks, and other sources.'],
            [null, '56.Financing Women Entities', 'Loans extended to women-owned or women-led enterprises.'],
            [null, '57.Financing SMEs', 'Loans extended to small and medium enterprises.'],
            [null, '58.Financing Youth Entities', 'Loans extended to youth-owned or youth-led enterprises.'],
            [null, '59.New Loans', 'Loans applied for and disbursed during the reporting quarter.'],
            [null, '60.NDFSP Staff, Board Members and Shareholders', 'Governance and staffing information disaggregated by gender.'],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet   = $event->sheet->getDelegate();
                $lastRow = $sheet->getHighestRow();

                // ── Tab colour — dark blue ────────────────────────────────────
                $sheet->getTabColor()->setRGB('1A3C6E');

                // ── Column widths ─────────────────────────────────────────────
                $sheet->getColumnDimension('A')->setWidth(4);
                $sheet->getColumnDimension('B')->setWidth(55);
                $sheet->getColumnDimension('C')->setWidth(85);

                // ── Row 2: header ─────────────────────────────────────────────
                $sheet->getStyle('B2:C2')->applyFromArray([
                    'font'      => ['bold' => true, 'size' => 11],
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFCC']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'AAAAAA']]],
                ]);
                $sheet->getRowDimension(2)->setRowHeight(18);

                // ── Section headers and data rows ─────────────────────────────
                $sections = ['A.BALANCE SHEET', 'B.INCOME STATEMENT', 'C.SUPPLEMENTARY INFORMATION'];

                for ($r = 3; $r <= $lastRow; $r++) {
                    $bVal = trim((string) $sheet->getCell("B{$r}")->getValue());

                    if (in_array($bVal, $sections)) {
                        // Section header — dark green background, white text
                        $sheet->mergeCells("B{$r}:C{$r}");
                        $sheet->getStyle("A{$r}:C{$r}")->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '003D22']],
                        ]);
                        $sheet->getRowDimension($r)->setRowHeight(20);
                        continue;
                    }

                    // Alternate row shading
                    $bg = $r % 2 === 0 ? 'F7F9FC' : 'FFFFFF';
                    $sheet->getStyle("B{$r}:C{$r}")->applyFromArray([
                        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                        'alignment' => ['vertical' => Alignment::VERTICAL_TOP, 'wrapText' => true],
                    ]);

                    // Bold the denomination (col B)
                    $sheet->getStyle("B{$r}")->getFont()->setBold(true);

                    $sheet->getRowDimension($r)->setRowHeight(15);
                }

                // ── Border around the whole table ─────────────────────────────
                $sheet->getStyle("B2:C{$lastRow}")->applyFromArray([
                    'borders' => [
                        'allBorders' => ['borderStyle' => Border::BORDER_THIN,   'color' => ['rgb' => 'CCCCCC']],
                        'outline'    => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => '003D22']],
                    ],
                ]);
            },
        ];
    }
}
