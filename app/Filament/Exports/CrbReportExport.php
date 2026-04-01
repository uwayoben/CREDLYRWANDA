<?php

namespace App\Filament\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/**
 * TransUnion CRB Individual Credit Report
 *
 * Location: app/Filament/Exports/CrbReportExport.php
 *
 * Produces 1 sheet matching the official TransUnion CRB template:
 *   Sheet 1 → Consumer  (individual borrowers — 74 columns)
 */
class CrbReportExport implements WithMultipleSheets
{
    protected Collection $loans;
    protected string     $institutionName;
    protected string     $reportingDate;   // YYYYMMDD format
    protected string     $institutionCode;

    public function __construct(
        Collection $loans,
        string     $institutionName  = '',
        string     $reportingDate    = '',
        string     $institutionCode  = ''
    ) {
        $this->loans           = $loans;
        $this->institutionName = $institutionName ?: config('app.name', 'Institution');
        $this->reportingDate   = $reportingDate ?: now()->format('Ymd');
        $this->institutionCode = $institutionCode;
    }

    public function sheets(): array
    {
        return [
            new CrbConsumerSheet(
                $this->loans,
                $this->institutionName,
                $this->reportingDate,
                $this->institutionCode,
            ),
        ];
    }
}
