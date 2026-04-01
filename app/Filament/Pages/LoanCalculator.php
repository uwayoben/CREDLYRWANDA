<?php

namespace App\Filament\Pages;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Filament\Pages\Page;

class LoanCalculator extends Page
{
    protected static string | BackedEnum | null $navigationIcon = Heroicon::Calculator;

    protected static ?string $navigationLabel = 'Loan Calculator';
    protected static ?string $title = 'Loan Calculator';
    protected static ?int $navigationSort = 0;
 


    protected string $view = 'filament.pages.loan-calculator';
}
