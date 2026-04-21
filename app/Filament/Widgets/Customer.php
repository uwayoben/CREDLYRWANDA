<?php

namespace App\Filament\Widgets;

use App\Models\Customer as CustomerModel;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CustomerStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $companyId = auth()->user()?->company_id;
        $isSuperAdmin = auth()->user()?->is_super_admin ?? false;

        $baseQuery = CustomerModel::query()
            ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $companyId));

        $total    = (clone $baseQuery)->count();
        $male     = (clone $baseQuery)->where('gender', 'male')->count();
        $female   = (clone $baseQuery)->where('gender', 'female')->count();
        $active   = (clone $baseQuery)->where('is_active', true)->count();
        $inactive = (clone $baseQuery)->where('is_active', false)->count();
        $employed = (clone $baseQuery)->where('employment_status', 'employed')->count();

        $malePercent   = $total > 0 ? round($male   / $total * 100) : 0;
        $femalePercent = $total > 0 ? round($female / $total * 100) : 0;
        $activePercent = $total > 0 ? round($active / $total * 100) : 0;

        // Sparkline: new customers per month for last 6 months
        $sparkline = collect(range(5, 0))->map(function ($monthsAgo) use ($baseQuery, $companyId, $isSuperAdmin) {
            return (clone CustomerModel::query()
                ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $companyId)))
                ->whereYear('created_at', now()->subMonths($monthsAgo)->year)
                ->whereMonth('created_at', now()->subMonths($monthsAgo)->month)
                ->count();
        })->toArray();

        return [
            // ── Total Customers ───────────────────────────────────────────────
            Stat::make('Total Customers', number_format($total))
                ->description('All registered borrowers')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary')
                ->chart($sparkline)
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            // ── Male Customers ────────────────────────────────────────────────
            Stat::make('Male Customers', number_format($male))
                ->description("{$malePercent}% of total customers")
                ->descriptionIcon('heroicon-m-user')
                ->color('info')
                ->chart(array_fill(0, 6, $male))
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            // ── Female Customers ──────────────────────────────────────────────
            Stat::make('Female Customers', number_format($female))
                ->description("{$femalePercent}% of total customers")
                ->descriptionIcon('heroicon-m-user')
                ->color('pink')
                ->chart(array_fill(0, 6, $female))
                ->extraAttributes([
                    'class' => 'cursor-pointer',
                ]),

            // ── Active Customers ──────────────────────────────────────────────
            Stat::make('Active Customers', number_format($active))
                ->description("{$activePercent}% currently active")
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success')
                ->chart(array_fill(0, 6, $active)),

            // ── Inactive Customers ────────────────────────────────────────────
            Stat::make('Inactive Customers', number_format($inactive))
                ->description($inactive > 0 ? 'Require follow-up' : 'All customers active')
                ->descriptionIcon($inactive > 0 ? 'heroicon-m-exclamation-circle' : 'heroicon-m-check-badge')
                ->color($inactive > 0 ? 'warning' : 'success'),

            // ── Employed Customers ────────────────────────────────────────────
            Stat::make('Employed Borrowers', number_format($employed))
                ->description($total > 0 ? round($employed / $total * 100) . '% have stable income' : 'No data')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('success'),
        ];
    }
}