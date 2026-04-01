<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * TransUnion Rwanda CRB Report
 * Sheets: Individual | Corporate | Shareholders | Directors | Guarantors | Bounced Cheques | Collateral
 */
class LoansCrbExport implements WithMultipleSheets
{
    protected Collection $loans;
    protected string     $institutionName;
    protected string     $reportingDate;

    public function __construct(
        Collection $loans,
        string $institutionName = '',
        ?string $reportingDate = null
    ) {
        $this->loans           = $loans;
        $this->institutionName = $institutionName ?: config('app.name', 'Institution');
        $this->reportingDate   = $reportingDate ?? now()->format('Ymd');
    }

    public function sheets(): array
    {
        return [
            new LoansCrbIndividualSheet($this->loans, $this->institutionName, $this->reportingDate),
        ];
    }
}