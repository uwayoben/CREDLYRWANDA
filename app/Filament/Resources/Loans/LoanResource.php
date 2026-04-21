<?php

namespace App\Filament\Resources\Loans;

use App\Filament\Resources\Loans\Pages\CreateLoan;
use App\Filament\Resources\Loans\Pages\EditLoan;
use App\Filament\Resources\Loans\Pages\ListLoans;
use App\Filament\Resources\Loans\Schemas\LoanForm;
use App\Filament\Resources\Loans\Tables\LoansTable;
use App\Models\Loan;
use App\Filament\Resources\LoanResource\RelationManagers\PaymentsRelationManager;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class LoanResource extends Resource
{
    protected static ?string $model = Loan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Banknotes;

    protected static ?string $recordTitleAttribute = 'Loan';

    protected static ?int $navigationSort = 2;

    // ── Navigation badge — shows total loans for this company ─────────────────

    public static function getNavigationBadge(): ?string
    {
        $user         = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;

        $count = static::getModel()::query()
            ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $user?->company_id))
            ->count();

        return (string) $count;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $user         = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;

        $total     = static::getModel()::query()
            ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $user?->company_id))
            ->count();

        $active    = static::getModel()::query()
            ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $user?->company_id))
            ->whereIn('loan_status', ['active', 'disbursed'])
            ->count();

        $completed = static::getModel()::query()
            ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $user?->company_id))
            ->where('loan_status', 'completed')
            ->count();

        return "{$total} total loans | {$active} active | {$completed} completed";
    }

    // ─────────────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return LoanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LoansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::make(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListLoans::route('/'),
            'create' => CreateLoan::route('/create'),
            'edit'   => EditLoan::route('/{record}/edit'),
        ];
    }
    public static function canAccess(): bool
{
    $user = Auth::user();

    return $user?->isManagingDirector() || $user?->isLoanOfficer() || $user?->isSuperAdmin();
}
}
