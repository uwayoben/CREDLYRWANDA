<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\WithSkipDuplicates;

class CustomersImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsOnError
{
    use SkipsErrors;

    public function model(array $row): ?Customer
    {
        return new Customer([
            'company_id'               => Auth::user()?->company_id,
            'names'                    => $row['full_names']           ?? $row['names']           ?? null,
            'national_id'              => $row['national_id']          ?? null,
            'date_of_birth'            => $row['date_of_birth']        ?? null,
            'gender'                   => strtolower($row['gender']    ?? ''),
            'marital_status'           => strtolower($row['marital_status'] ?? ''),
            'phone'                    => $row['phone_number']         ?? $row['phone']           ?? null,
            'email'                    => $row['email_address']        ?? $row['email']           ?? null,
            'employment_status'        => strtolower(str_replace(' ', '_', $row['employment_status'] ?? '')),
            'employer_name'            => $row['employer_name']        ?? null,
            'province'                 => $row['province']             ?? null,
            'district'                 => $row['district']             ?? null,
            'sector'                   => $row['sector']               ?? null,
            'cell'                     => $row['cell']                 ?? null,
            'village'                  => $row['village']              ?? null,
            'relationship_with_ndfsp'  => $row['relationship_with_ndfsp'] ?? null,
            'spouse_name'              => $row['spouse_name']          ?? null,
            'spouse_phone'             => $row['spouse_phone']         ?? null,
            'spouse_id_number'         => $row['spouse_national_id']   ?? null,
            'marital_property_regime'  => $row['marital_property_regime'] ?? null,
            'is_active'                => 1,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_names'   => 'nullable|string',
            'names'        => 'nullable|string',
            'national_id'  => 'nullable|string',
            'phone_number' => 'nullable|string',
            'phone'        => 'nullable|string',
        ];
    }
}
