<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * BNR Loan Classification Report
 * Matches official BNR NDFSP template exactly:
 *   A1.1 Explanatory Note
 *   A1.2. FS  (Financial Statements)
 *   A1.3. Normal Loans
 *   A1.4. Watch
 *   A1.5. Substandard
 *   A1.6. Doubtful
 *   A1.7 Loss
 *   A1.8. Restructured loans
 *   A1.9. Written off
 */
class LoansBnrExport implements WithMultipleSheets
{
    protected Collection $loans;
    protected string     $reportingDate;
    protected string     $institutionName;
    protected string     $sector;
    protected string     $district;

    /** BNR class keys → [sheet name, PAR label, prov rate] */
    public const CLASSES = [
        'normal'       => ['sheet' => 'A1.3. Normal Loans',       'par' => 'Portfolio At Risk 0 days',                    'rate' => 0.00, 'label' => 'Loan Classification Report (NORMAL)'],
        'watch'        => ['sheet' => 'A1.4. Watch',               'par' => 'Portfolio At Risk 1 to 89 days',              'rate' => 0.01, 'label' => 'Loan Classification Report (WATCH )'],
        'substandard'  => ['sheet' => 'A1.5. Substandard',         'par' => 'Portfolio At Risk 90 to 179 days in arrears', 'rate' => 0.20, 'label' => 'Loan Classification Report (SUBSTANDARD )'],
        'doubtful'     => ['sheet' => 'A1.6. Doubtful',            'par' => 'Portfolio At Risk 180 to 359 days in arrears','rate' => 0.50, 'label' => 'Loan Classification Report (DOUBTFUL)'],
        'loss'         => ['sheet' => 'A1.7 Loss',                 'par' => 'Portfolio at risk 360 - 719 days in arrears', 'rate' => 1.00, 'label' => 'Loan Classification Report ( LOSS )'],
        'restructured' => ['sheet' => 'A1.8. Restructured loans',  'par' => 'Renegotiated Loans',                          'rate' => 0.00, 'label' => 'M.V.Loan Classification Report'],
    ];

    public function __construct(
        Collection $loans,
        string $institutionName = '',
        ?string $reportingDate  = null,
        string $sector          = '',
        string $district        = ''
    ) {
        $this->loans           = $loans;
        $this->institutionName = $institutionName ?: config('app.name', 'Institution');
        $this->reportingDate   = $reportingDate ?? now()->format('d/m/Y');
        $this->sector          = $sector;
        $this->district        = $district;
    }

    public function sheets(): array
    {
        $sheets = [];

        // A1.1 — Explanatory Note
        $sheets[] = new BnrExplanatoryNoteSheet();

        // A1.2 — Financial Statements
        $sheets[] = new BnrFinancialStatementSheet(
            $this->loans,
            $this->institutionName,
            $this->reportingDate,
            $this->sector,
            $this->district
        );

        // A1.3 – A1.8 — One sheet per loan class
        foreach (self::CLASSES as $classKey => $meta) {
            $filtered = $this->loans
                ->filter(fn ($l) => ($l->loan_class ?? 'normal') === $classKey)
                ->values();

            $sheets[] = new BnrLoanClassSheet(
                $filtered,
                $classKey,
                $meta,
                $this->institutionName,
                $this->reportingDate
            );
        }

        // A1.9 — Written Off
        $writtenOff = $this->loans
            ->filter(fn ($l) => ($l->loan_class ?? '') === 'written_off')
            ->values();

        $sheets[] = new BnrWrittenOffSheet(
            $writtenOff,
            $this->institutionName,
            $this->reportingDate
        );

        return $sheets;
    }
}