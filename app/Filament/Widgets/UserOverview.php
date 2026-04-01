<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class UserOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
  protected ?string $pollingInterval = '10s';
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        $onlineUsers = User::where('last_seen_at', '>=', now()->subMinutes(5))->count();

        return [
            Stat::make('Total Companies', Company::count())
                ->description('Number of registered companies')
                ->descriptionIcon('heroicon-o-building-office')
                ->color('success')
                ->chart([7, 3, 10, 5, 15, 10, 20]),

            Stat::make('Total Users', User::count())
                ->description('Number of system users')
                ->descriptionIcon('heroicon-o-users')
                ->color('info')
                ->chart([15, 20, 25, 30, 35, 40, 45]),

            Stat::make('Active Companies', Company::where('is_active', true)->count())
                ->description('Currently active companies')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('primary')
                ->chart([5, 8, 12, 8, 15, 12, 18]),

            Stat::make('Online Users', $onlineUsers)
                ->description('Active in the last 5 minutes')
                ->descriptionIcon('heroicon-o-signal')
                ->color($onlineUsers > 0 ? 'success' : 'gray')
                ->chart([1, 2, 1, 3, 2, 4, $onlineUsers]),
        ];
    }

    public static function canView(): bool
    {
        return auth()->user()?->is_super_admin ?? false;
    }
}
