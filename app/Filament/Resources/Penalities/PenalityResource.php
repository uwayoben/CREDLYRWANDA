<?php

namespace App\Filament\Resources\Penalities;

use App\Filament\Resources\Penalities\Pages\CreatePenality;
use App\Filament\Resources\Penalities\Pages\EditPenality;
use App\Filament\Resources\Penalities\Pages\ListPenalities;
use App\Filament\Resources\Penalities\Schemas\PenalityForm;
use App\Filament\Resources\Penalities\Tables\PenalitiesTable;
use App\Models\Penality;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PenalityResource extends Resource
{
    protected static ?string $model = Penality::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ExclamationTriangle;

    protected static ?string $recordTitleAttribute = 'Penality';
        protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return PenalityForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PenalitiesTable::configure($table);
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
            'index' => ListPenalities::route('/'),
            'create' => CreatePenality::route('/create'),
            'edit' => EditPenality::route('/{record}/edit'),
        ];
    }
}
