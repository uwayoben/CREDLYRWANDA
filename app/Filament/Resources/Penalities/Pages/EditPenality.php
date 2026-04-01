<?php

namespace App\Filament\Resources\Penalities\Pages;

use App\Filament\Resources\Penalities\PenalityResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPenality extends EditRecord
{
    protected static string $resource = PenalityResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
