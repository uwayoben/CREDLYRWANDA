<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('company_id')
                    ->numeric(),
                TextEntry::make('names'),
                TextEntry::make('national_id'),
                TextEntry::make('date_of_birth')
                    ->date(),
                TextEntry::make('gender')
                    ->badge(),
                TextEntry::make('province'),
                TextEntry::make('district'),
                TextEntry::make('sector'),
                TextEntry::make('cell'),
                TextEntry::make('village'),
                TextEntry::make('phone'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('marital_status')
                    ->badge(),
                TextEntry::make('employer_name')
                    ->placeholder('-'),
                TextEntry::make('employment_status')
                    ->badge()
                    ->placeholder('-'),
                TextEntry::make('relationship_with_ndfsp')
                    ->placeholder('-'),
                TextEntry::make('photo')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->boolean(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
