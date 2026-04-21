<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Loan;
use App\Models\OtherIncome;
use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LoanStatus extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '30s';

    public ?string $activeFilter = 'all';

    protected function getFilters(): ?array
    {
        return [
            'all'          => 'All Time',
            'today'        => 'Today',
            'this_week'    => 'This Week',
            'this_month'   => 'This Month',
            'last_month'   => 'Last Month',
            'this_quarter' => 'This Quarter',
            'last_quarter' => 'Last Quarter',
            'this_year'    => 'This Year',
            'last_year'    => 'Last Year',
        ];
    }

    private function getDateRange(): ?array
    {
        return match ($this->activeFilter) {
            'today'        => [Carbon::today(),                                Carbon::today()->endOfDay()],
            'this_week'    => [Carbon::now()->startOfWeek(),                   Carbon::now()->endOfWeek()],
            'this_month'   => [Carbon::now()->startOfMonth(),                  Carbon::now()->endOfMonth()],
            'last_month'   => [Carbon::now()->subMonth()->startOfMonth(),      Carbon::now()->subMonth()->endOfMonth()],
            'this_quarter' => [Carbon::now()->startOfQuarter(),                Carbon::now()->endOfQuarter()],
            'last_quarter' => [Carbon::now()->subQuarter()->startOfQuarter(),  Carbon::now()->subQuarter()->endOfQuarter()],
            'this_year'    => [Carbon::now()->startOfYear(),                   Carbon::now()->endOfYear()],
            'last_year'    => [Carbon::now()->subYear()->startOfYear(),        Carbon::now()->subYear()->endOfYear()],
            default        => null,
        };
    }

    private function applyDateFilter($query, ?array $range): mixed
    {
        if (! $range) return $query;
        return $query->whereBetween('created_at', $range);
    }

    private function applyDateFilterOnPayments($query, ?array $range): mixed
    {
        if (! $range) return $query;
        return $query->whereBetween('payment_date', $range);
    }

    protected function getStats(): array
    {
        $user         = Auth::user();
        $companyId    = $user?->company_id;
        $isManager    = $user?->isManagingDirector() ?? false;
        $isReception  = $user?->isReceptionist() ?? false;
        $range        = $this->getDateRange();

        // Reception sees nothing — return empty so widget renders blank
        if ($isReception) {
            return [];
        }

        // ── Base loan query — all time (for running balances) ─────────────────
        $baseQuery = Loan::query()->where('company_id', $companyId);

        // ── Filtered loan query — respects selected period ────────────────────
        $loanQuery = $this->applyDateFilter((clone $baseQuery), $range);

        // ── Filtered payment query ────────────────────────────────────────────
        $payQuery = $this->applyDateFilterOnPayments(
            Payment::query()->where('company_id', $companyId),
            $range
        );

        // ── Loan counts & disbursements (filtered) ────────────────────────────
        $totalLoans     = (clone $loanQuery)->count();
        $totalPrincipal = (clone $loanQuery)->sum('principal_amount');
        $activeLoans    = (clone $loanQuery)->where('loan_status', 'active')->count();
        $completedLoans = (clone $loanQuery)->where('loan_status', 'completed')->count();
        $defaultedLoans = (clone $loanQuery)->where('loan_status', 'defaulted')->count();

        // ── Running balances — always all time ────────────────────────────────
        $totalAmountPaid           = (clone $baseQuery)->sum('amount_paid');
        $totalOutstanding          = (clone $baseQuery)->sum('remaining_balance');
        $totalPrincipalPaid        = (clone $baseQuery)->sum('principal_paid');
        $totalInterestPaid         = (clone $baseQuery)->sum('interest_paid');
        $totalOutstandingPrincipal = (clone $baseQuery)
            ->selectRaw('SUM(GREATEST(principal_amount - principal_paid, 0)) as total')
            ->value('total') ?? 0;

        // ── Fees — filtered by loan creation date ─────────────────────────────
        $totalProcessingFee  = (clone $loanQuery)->sum('processing_fee');
        $totalApplicationFee = (clone $loanQuery)->sum('application_fee');
        $totalFees           = $totalProcessingFee + $totalApplicationFee;

        // ── Penalty — filtered by payment date ───────────────────────────────
        $totalPenaltyPaid = (clone $payQuery)->sum('penalty_paid');

        // ── Other Income — filtered ───────────────────────────────────────────
        $otherIncomeQuery      = $this->applyDateFilter(
            OtherIncome::query()->where('company_id', $companyId),
            $range
        );
        $totalOtherIncome      = (clone $otherIncomeQuery)->sum('amount');
        $totalOtherIncomeCount = (clone $otherIncomeQuery)->count();

        // ── Total Earnings ────────────────────────────────────────────────────
        $totalEarnings = $totalInterestPaid + $totalFees + $totalPenaltyPaid + $totalOtherIncome;

        // ── Expenses — filtered ───────────────────────────────────────────────
        $totalExpenses = $this->applyDateFilter(
            Expense::query()->where('company_id', $companyId),
            $range
        )->sum('amount');

        // ── Net Profit ────────────────────────────────────────────────────────
        $netProfit = $totalEarnings - $totalExpenses;

        // ── Period label ──────────────────────────────────────────────────────
        $period = match ($this->activeFilter) {
            'today'        => 'today',
            'this_week'    => 'this week',
            'this_month'   => 'this month',
            'last_month'   => 'last month',
            'this_quarter' => 'this quarter',
            'last_quarter' => 'last quarter',
            'this_year'    => 'this year',
            'last_year'    => 'last year',
            default        => 'all time',
        };

        // ── Build stats — all roles except reception ───────────────────────────
        $stats = [
            Stat::make('Total Loans', $totalLoans)
                ->description($activeLoans . ' active · ' . $completedLoans . ' completed · ' . $period)
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary')
                ->chart([1, 3, 5, 4, 6, 8, $totalLoans]),

            Stat::make('Total Disbursed', 'RWF ' . number_format($totalPrincipal, 0))
                ->description('Principal lent out — ' . $period)
                ->descriptionIcon('heroicon-o-arrow-down-circle')
                ->color('warning')
                ->chart([5, 10, 8, 15, 12, 18, $totalLoans]),

            Stat::make('Total Collected', 'RWF ' . number_format($totalAmountPaid, 0))
                ->description('Total repayments received (all time)')
                ->descriptionIcon('heroicon-o-arrow-up-circle')
                ->color('success')
                ->chart([2, 4, 6, 5, 8, 10, $totalLoans]),

            Stat::make('Outstanding Balance', 'RWF ' . number_format($totalOutstanding, 0))
                ->description($defaultedLoans . ' defaulted loan(s) (all time)')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color($defaultedLoans > 0 ? 'danger' : 'info')
                ->chart([8, 6, 7, 5, 4, 3, $totalLoans]),

            Stat::make('Principal Paid', 'RWF ' . number_format($totalPrincipalPaid, 0))
                ->description('Total principal recovered (all time)')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->chart([1, 2, 4, 3, 6, 5, $totalLoans]),

            Stat::make('Outstanding Principal', 'RWF ' . number_format($totalOutstandingPrincipal, 0))
                ->description('Principal yet to be recovered (all time)')
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color($totalOutstandingPrincipal > 0 ? 'danger' : 'success')
                ->chart([8, 7, 6, 5, 4, 3, $totalLoans]),

            Stat::make('Interest Earned', 'RWF ' . number_format($totalInterestPaid, 0))
                ->description('Total interest collected (all time)')
                ->descriptionIcon('heroicon-o-percent-badge')
                ->color('info')
                ->chart([1, 2, 3, 4, 5, 6, $totalLoans]),

            Stat::make('Penalty Collected', 'RWF ' . number_format($totalPenaltyPaid, 0))
                ->description('Penalties collected — ' . $period)
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->chart([1, 1, 2, 2, 3, 3, $totalLoans]),

            Stat::make('Total Fees', 'RWF ' . number_format($totalFees, 0))
                ->description(
                    'Processing: RWF ' . number_format($totalProcessingFee, 0) .
                    ' · App: RWF '     . number_format($totalApplicationFee, 0) .
                    ' — ' . $period
                )
                ->descriptionIcon('heroicon-o-tag')
                ->color('info')
                ->chart([1, 2, 2, 3, 3, 4, $totalLoans]),

            Stat::make('Other Income', 'RWF ' . number_format($totalOtherIncome, 0))
                ->description($totalOtherIncomeCount . ' record(s) — ' . $period)
                ->descriptionIcon('heroicon-o-arrow-trending-up')
                ->color('success')
                ->chart([1, 2, 2, 3, 4, 5, $totalOtherIncomeCount]),
        ];

        // ── Manager-only stats ────────────────────────────────────────────────
        if ($isManager) {
            $stats[] = Stat::make('Total Earnings', 'RWF ' . number_format($totalEarnings, 0))
                ->description('Interest + Fees + Penalty + Other Income — ' . $period)
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success')
                ->chart([2, 4, 5, 6, 8, 9, $totalLoans]);

            $stats[] = Stat::make('Total Expenses', 'RWF ' . number_format($totalExpenses, 0))
                ->description('All recorded expenses — ' . $period)
                ->descriptionIcon('heroicon-o-arrow-trending-down')
                ->color('danger')
                ->chart([3, 5, 4, 6, 5, 7, $totalLoans]);

            $stats[] = Stat::make('Net Profit', 'RWF ' . number_format($netProfit, 0))
                ->description(
                    ($netProfit >= 0 ? 'Profitable' : 'Running at a loss') . ' — ' . $period
                )
                ->descriptionIcon($netProfit >= 0
                    ? 'heroicon-o-check-badge'
                    : 'heroicon-o-exclamation-circle'
                )
                ->color($netProfit >= 0 ? 'success' : 'danger')
                ->chart([1, 3, 4, 5, 6, 7, $totalLoans]);
        }

        return $stats;
    }

    public static function canView(): bool
    {
        $user = Auth::user();

        // Super admin never sees this widget (they have no company context)
        if ($user?->is_super_admin ?? false) {
            return false;
        }

        // Reception sees nothing
        if ($user?->isReceptionist() ?? false) {
            return false;
        }

        return $user !== null;
    }
}