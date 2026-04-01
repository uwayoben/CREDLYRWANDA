<?php

namespace App\Filament\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * BNR NDFSP Credit Classification Report
 *
 * Location: app/Filament/Exports/BnrReportExport.php
 *
 * Produces 9 sheets exactly matching the official BNR template:
 *
 *   Sheet 1 → A1.1  Explanatory Note
 *   Sheet 2 → A1.2. FS               (Financial Statements)
 *   Sheet 3 → A1.3. Normal Loans
 *   Sheet 4 → A1.4. Watch
 *   Sheet 5 → A1.5. Substandard
 *   Sheet 6 → A1.6. Doubtful
 *   Sheet 7 → A1.7 Loss
 *   Sheet 8 → A1.8. Restructured loans
 *   Sheet 9 → A1.9. Written off
 */
class BnrReportExport implements WithMultipleSheets
{
    protected Collection $loans;
    protected string     $institutionName;
    protected string     $reportingDate;
    protected string     $sector;
    protected string     $district;

    /**
     * BNR loan classification definitions.
     *
     * Keys must match the loan_class values stored in your loans table.
     * Each entry defines:
     *   sheet → exact tab name as required by BNR
     *   par   → Portfolio At Risk description shown on the sheet
     *   rate  → provisioning rate as a decimal (0.01 = 1%)
     *   prov  → minimum provisioning label shown on the sheet
     *   label → report name shown in the header block
     */
    public const CLASSES = [
        'normal' => [
            'sheet' => 'A1.3. Normal Loans',
            'par'   => 'Portfolio At Risk 0 days',
            'rate'  => 0.00,
            'prov'  => 'Minimum provisioning rate required : 0%',
            'label' => 'Loan Classification Report (NORMAL)',
        ],
        'watch' => [
            'sheet' => 'A1.4. Watch',
            'par'   => 'Portfolio At Risk 1 to 89 days',
            'rate'  => 0.01,
            'prov'  => 'Minimum provisioning rate required : 1%',
            'label' => 'Loan Classification Report (WATCH )',
        ],
        'substandard' => [
            'sheet' => 'A1.5. Substandard',
            'par'   => 'Portfolio At Risk 90 to 179 days in arrears ',
            'rate'  => 0.20,
            'prov'  => 'Minimum provisioning rate required : 20%',
            'label' => 'Loan Classification Report (SUBSTANDARD )',
        ],
        'doubtful' => [
            'sheet' => 'A1.6. Doubtful',
            'par'   => 'Portfolio At Risk 180 to 359 days in arrears',
            'rate'  => 0.50,
            'prov'  => 'Minimum provisioning rate required : 50%',
            'label' => 'Loan Classification Report (DOUBTFUL)',
        ],
        'loss' => [
            'sheet' => 'A1.7 Loss',
            'par'   => 'Portfolio at risk 360 - 719 days in arrears ',
            'rate'  => 1.00,
            'prov'  => 'Minimum provisioning rate required: 100%',
            'label' => 'Loan Classification Report ( LOSS )',
        ],
        'restructured' => [
            'sheet' => 'A1.8. Restructured loans',
            'par'   => 'Renegotiated Loans',
            'rate'  => 0.00,
            'prov'  => '',
            'label' => 'M.V.Loan Classification Report',
        ],
    ];

    public function __construct(
        Collection $loans,
        string     $institutionName = '',
        ?string    $reportingDate   = null,
        string     $sector          = '',
        string     $district        = ''
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

        // ── Sheet 1: A1.1 Explanatory Note ───────────────────────────────────
        $sheets[] = new BnrExplanatorySheet();

        // ── Sheet 2: A1.2. FS — Financial Statements ─────────────────────────
        $sheets[] = new BnrFsSheet(
            $this->loans,
            $this->institutionName,
            $this->reportingDate,
            $this->sector,
            $this->district,
        );

        // ── Sheets 3–8: A1.3 – A1.8 one per loan class ───────────────────────
        foreach (self::CLASSES as $classKey => $meta) {
            $filtered = $this->loans
                ->filter(fn ($l) => ($l->loan_class ?? 'normal') === $classKey)
                ->values();

            $sheets[] = new BnrClassSheet(
                $filtered,
                $classKey,
                $meta,
                $this->institutionName,
                $this->reportingDate,
            );
        }

        // ── Sheet 9: A1.9. Written off ────────────────────────────────────────
        $writtenOff = $this->loans
            ->filter(fn ($l) => ($l->loan_class ?? '') === 'written_off')
            ->values();

        $sheets[] = new BnrWrittenOffSheet(
            $writtenOff,
            $this->institutionName,
            $this->reportingDate,
        );

        return $sheets;
    }
}