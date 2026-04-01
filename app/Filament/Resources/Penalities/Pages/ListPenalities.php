<?php

namespace App\Filament\Resources\Penalities\Pages;

use App\Filament\Resources\Penalities\PenalityResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPenalities extends ListRecords
{
    protected static string $resource = PenalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
