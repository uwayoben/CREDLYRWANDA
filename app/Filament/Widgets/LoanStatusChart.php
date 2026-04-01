<?php

namespace App\Filament\Widgets;

use App\Models\Loan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class LoanStatusChart extends ChartWidget
{
    protected ?string $heading  = 'Loan Status Distribution';
    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $user      = Auth::user();
        $companyId = $user?->company_id;

        $statuses = [
            'pending'     => 'Pending',
            'approved'    => 'Approved',
            'disbursed'   => 'Disbursed',
            'active'      => 'Active',
            'completed'   => 'Completed',
            'defaulted'   => 'Defaulted',
            'written_off' => 'Written Off',
            'rejected'    => 'Rejected',
        ];

        $colors = [
            'pending'     => ['bg' => 'rgba(148, 163, 184, 0.8)', 'border' => 'rgb(148, 163, 184)'], // gray
            'approved'    => ['bg' => 'rgba(59, 130, 246, 0.8)',  'border' => 'rgb(59, 130, 246)'],  // blue
            'disbursed'   => ['bg' => 'rgba(245, 158, 11, 0.8)',  'border' => 'rgb(245, 158, 11)'],  // amber
            'active'      => ['bg' => 'rgba(34, 197, 94, 0.8)',   'border' => 'rgb(34, 197, 94)'],   // green
            'completed'   => ['bg' => 'rgba(16, 185, 129, 0.8)',  'border' => 'rgb(16, 185, 129)'],  // emerald
            'defaulted'   => ['bg' => 'rgba(239, 68, 68, 0.8)',   'border' => 'rgb(239, 68, 68)'],   // red
            'written_off' => ['bg' => 'rgba(127, 29, 29, 0.8)',   'border' => 'rgb(127, 29, 29)'],   // dark red
            'rejected'    => ['bg' => 'rgba(251, 146, 60, 0.8)',  'border' => 'rgb(251, 146, 60)'],  // orange
        ];

        $counts = [];
        $total  = 0;

        foreach ($statuses as $key => $label) {
            $count       = Loan::query()
                ->where('company_id', $companyId)
                ->where('loan_status', $key)
                ->count();
            $counts[$key] = $count;
            $total       += $count;
        }

        $data            = [];
        $backgroundColors = [];
        $borderColors    = [];
        $labels          = [];

        foreach ($statuses as $key => $label) {
            $count      = $counts[$key];
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;

            // Only include statuses that have at least 1 loan
            if ($count > 0) {
                $data[]             = $count;
                $backgroundColors[] = $colors[$key]['bg'];
                $borderColors[]     = $colors[$key]['border'];
                $labels[]           = "{$label} ({$count} — {$percentage}%)";
            }
        }

        return [
            'datasets' => [
                [
                    'label'           => 'Loans by Status',
                    'data'            => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor'     => $borderColors,
                    'borderWidth'     => 2,
                    'hoverOffset'     => 15,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'cutout'  => '65%',
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels'   => [
                        'padding'        => 20,
                        'usePointStyle'  => true,
                        'pointStyle'     => 'circle',
                    ],
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) {
                            let value = context.raw || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = total > 0 ? Math.round((value / total) * 100) : 0;
                            return " " + value + " loans (" + percentage + "%)";
                        }',
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
