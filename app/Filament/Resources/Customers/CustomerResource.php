<?php

namespace App\Filament\Resources\Customers;

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Filament\Resources\Customers\Schemas\CustomerForm;
use App\Filament\Resources\Customers\Schemas\CustomerInfolist;
use App\Filament\Resources\Customers\Tables\CustomersTable;
use App\Models\Customer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::UserPlus;

    protected static ?string $recordTitleAttribute = 'Customer';

    protected static ?int $navigationSort = 1;

    // ── Navigation badge — shows total customers for this company ─────────────

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
        return 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $user         = Auth::user();
        $isSuperAdmin = $user?->is_super_admin ?? false;

        $total  = static::getModel()::query()
            ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $user?->company_id))
            ->count();

        $active = static::getModel()::query()
            ->when(! $isSuperAdmin, fn ($q) => $q->where('company_id', $user?->company_id))
            ->where('is_active', true)
            ->count();

        $inactive = $total - $active;

        return "{$total} total customers | {$active} active | {$inactive} inactive";
    }

    // ─────────────────────────────────────────────────────────────────────────

    public static function form(Schema $schema): Schema
    {
        return CustomerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CustomerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListCustomers::route('/'),
            'create' => CreateCustomer::route('/create'),
            'view'   => ViewCustomer::route('/{record}'),
            'edit'   => EditCustomer::route('/{record}/edit'),
        ];
    }
}
