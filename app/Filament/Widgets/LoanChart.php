<?php

namespace App\Filament\Widgets;

use App\Models\Loan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LoanChartWidget extends ChartWidget
{
    protected ?string $heading   = 'Loans & Interest by Month';
protected ?string $maxHeight = '300px';
protected ?string $pollingInterval  = '30s';


    protected static ?int $sort         = 2;

    // protected int|string|array $columnSpan = 'full';

    public ?string $filter = 'year';

    protected function getFilters(): ?array
    {
        return [
            'year'       => 'This Year',
            'last_year'  => 'Last Year',
            '6_months'   => 'Last 6 Months',
            '3_months'   => 'Last 3 Months',
        ];
    }

    protected function getData(): array
    {
        $user      = Auth::user();
        $companyId = $user?->company_id;

        // ── Date range based on filter ────────────────────────────
        $now = now();

        [$startDate, $months] = match ($this->filter) {
            'last_year' => [$now->copy()->subYear()->startOfYear(), 12],
            '6_months'  => [$now->copy()->subMonths(5)->startOfMonth(), 6],
            '3_months'  => [$now->copy()->subMonths(2)->startOfMonth(), 3],
            default     => [$now->copy()->startOfYear(), $now->month],
        };

        // ── Build month labels ────────────────────────────────────
        $labels          = [];
        $loanCounts      = [];
        $principalData   = [];
        $interestData    = [];

        for ($i = 0; $i < $months; $i++) {
            $month = $startDate->copy()->addMonths($i);
            $labels[] = $month->format('M Y');

            $monthData = Loan::query()
                ->where('company_id', $companyId)
                ->whereYear('disbursement_date', $month->year)
                ->whereMonth('disbursement_date', $month->month)
                ->select([
                    DB::raw('COUNT(*) as loan_count'),
                    DB::raw('SUM(principal_amount) as total_principal'),
                    DB::raw('SUM(total_interest) as total_interest'),
                ])
                ->first();

            $loanCounts[]    = $monthData->loan_count    ?? 0;
            $principalData[] = $monthData->total_principal ?? 0;
            $interestData[]  = $monthData->total_interest  ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Principal Disbursed (RWF)',
                    'data'            => $principalData,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.15)',
                    'borderColor'     => 'rgba(59, 130, 246, 1)',
                    'borderWidth'     => 2,
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 4,
                    'pointBackgroundColor' => 'rgba(59, 130, 246, 1)',
                    'yAxisID'         => 'y',
                ],
                [
                    'label'           => 'Total Interest (RWF)',
                    'data'            => $interestData,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.15)',
                    'borderColor'     => 'rgba(16, 185, 129, 1)',
                    'borderWidth'     => 2,
                    'fill'            => true,
                    'tension'         => 0.4,
                    'pointRadius'     => 4,
                    'pointBackgroundColor' => 'rgba(16, 185, 129, 1)',
                    'yAxisID'         => 'y',
                ],
                [
                    'label'           => 'Number of Loans',
                    'data'            => $loanCounts,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.8)',
                    'borderColor'     => 'rgba(245, 158, 11, 1)',
                    'borderWidth'     => 2,
                    'type'            => 'bar',
                    'yAxisID'         => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'interaction' => [
                'mode'      => 'index',
                'intersect' => false,
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => "function(context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.dataset.yAxisID === 'y') {
                                label += 'RWF ' + context.parsed.y.toLocaleString();
                            } else {
                                label += context.parsed.y + ' loans';
                            }
                            return label;
                        }",
                    ],
                ],
            ],
            'scales' => [
                'y' => [
                    'type'     => 'linear',
                    'display'  => true,
                    'position' => 'left',
                    'title'    => [
                        'display' => true,
                        'text'    => 'Amount (RWF)',
                    ],
                    'ticks' => [
                        'callback' => "function(value) {
                            if (value >= 1000000) return 'RWF ' + (value/1000000).toFixed(1) + 'M';
                            if (value >= 1000) return 'RWF ' + (value/1000).toFixed(0) + 'K';
                            return 'RWF ' + value;
                        }",
                    ],
                ],
                'y1' => [
                    'type'     => 'linear',
                    'display'  => true,
                    'position' => 'right',
                    'title'    => [
                        'display' => true,
                        'text'    => 'Number of Loans',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user !== null && ! ($user->is_super_admin ?? false);
    }
}
