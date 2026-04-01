<?php

namespace App\Filament\Resources\Loans\Pages;

use App\Filament\Resources\Loans\LoanResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateLoan extends CreateRecord
{
    protected static string $resource = LoanResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        if (! ($user->is_super_admin ?? false)) {
            $data['company_id'] = $user->company_id;
        }

        $data['created_by'] = $user->id;

        // Recalculate totals server-side — never trust disabled field values alone
        $principal = (float) ($data['principal_amount'] ?? 0);
        $rate      = (float) ($data['interest_rate'] ?? 0) / 100;
        $n         = (int)   ($data['number_of_installments'] ?? 0);
        $type      = $data['interest_type'] ?? 'declining';

        if ($principal > 0 && $n > 0) {
            if ($type === 'flat') {
                $totalInterest = $principal * $rate * $n;
            } else {
                if ($rate == 0) {
                    $totalInterest = 0;
                } else {
                    $emi           = ($principal * $rate * pow(1 + $rate, $n)) / (pow(1 + $rate, $n) - 1);
                    $totalInterest = ($emi * $n) - $principal;
                }
            }

            $data['total_interest']    = round($totalInterest, 2);
            $data['total_amount']      = round($principal + $totalInterest, 2);
            $data['remaining_balance'] = round($principal + $totalInterest, 2);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->generateInstallments();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
