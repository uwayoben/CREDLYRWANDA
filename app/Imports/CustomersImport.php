<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * Flexible customer importer.
 * Accepts any heading row position and many column name variations.
 */
class CustomersImport implements ToCollection, WithHeadingRow
{
    public int   $importedCount = 0;
    public int   $skippedCount  = 0;
    public array $errors        = [];
    public array $detectedKeys  = []; // ← stores real keys from the file

    protected int $companyId;
    protected int $headingRowNumber;

    public function __construct(?int $companyId = null, int $headingRow = 3)
    {
        $this->companyId        = $companyId ?? Auth::user()?->company_id ?? 0;
        $this->headingRowNumber = $headingRow;
    }

    public function headingRow(): int
    {
        return $this->headingRowNumber;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $row = $row->toArray();

            // ── On first row: store keys for debugging ────────────────────────
            if ($index === 0) {
                $this->detectedKeys = array_keys($row);
                Log::info('CustomersImport — detected keys', $this->detectedKeys);
                Log::info('CustomersImport — first row values', $row);
            }

            // ── Get name — try every possible column name variation ───────────
            $name = $this->get($row, [
                'full_name', 'full_names', 'name', 'names',
                'customer_name', 'borrower_name', 'client_name',
                'nom', 'amazina', 'beneficiary_name',
            ]);

            if (trim((string) $name) === '') {
                $this->skippedCount++;
                continue;
            }

            $name = trim((string) $name);

            // ── National ID ───────────────────────────────────────────────────
            $nationalId = trim((string) $this->get($row, [
                'national_id', 'national_id_number', 'id_number',
                'nid', 'id', 'nin',
            ]));

            // Skip duplicates
            if ($nationalId !== '') {
                if (Customer::where('company_id', $this->companyId)
                        ->where('national_id', $nationalId)
                        ->exists()) {
                    $this->skippedCount++;
                    $this->errors[] = "Row " . ($index + $this->headingRowNumber + 1) .
                        ": {$name} (ID: {$nationalId}) already exists — skipped.";
                    continue;
                }
            }

            // ── Save ──────────────────────────────────────────────────────────
            try {
                Customer::create([
                    'company_id'              => $this->companyId,
                    'names'                   => $name,
                    'national_id'             => $nationalId ?: null,
                    'date_of_birth'           => $this->parseDate(
                        $this->get($row, ['date_of_birth', 'dob', 'birth_date', 'birthdate'])
                    ),
                    'gender'                  => strtolower(trim((string) $this->get($row, ['gender', 'sex']))),
                    'marital_status'          => strtolower(trim((string) $this->get($row, ['marital_status', 'marital', 'civil_status']))),
                    'phone'                   => $this->get($row, ['phone', 'phone_number', 'mobile', 'telephone', 'mobile_telephone']),
                    'email'                   => $this->get($row, ['email', 'email_address', 'mail']),
                    'employment_status'       => strtolower(str_replace(' ', '_', trim((string) $this->get($row, ['employment_status', 'employment', 'occupation_status'])))),
                    'employer_name'           => $this->get($row, ['employer_name', 'employer', 'company', 'workplace']),
                    'province'                => $this->get($row, ['province', 'physical_address_province']),
                    'district'                => $this->get($row, ['district', 'physical_address_district']),
                    'sector'                  => $this->get($row, ['sector', 'physical_address_sector']),
                    'cell'                    => $this->get($row, ['cell', 'physical_address_cell']),
                    'village'                 => $this->get($row, ['village', 'physical_address_village']),
                    'relationship_with_ndfsp' => $this->get($row, ['relationship_with_ndfsp', 'relationship']),
                    'spouse_name'             => $this->get($row, ['spouse_name', 'spouses_name']),
                    'spouse_phone'            => $this->get($row, ['spouse_phone', 'spouse_telephone']),
                    'spause_id_number'        => $this->get($row, ['spouse_national_id', 'spouse_id', 'spouse_nid']),
                    'marital_property_regime' => $this->get($row, ['marital_property_regime', 'property_regime']),
                    'is_active'               => 1,
                ]);

                $this->importedCount++;

            } catch (\Exception $e) {
                $this->skippedCount++;
                $this->errors[] = "Row " . ($index + $this->headingRowNumber + 1) .
                    ": {$name} — " . $e->getMessage();
                Log::error('CustomersImport row error', ['name' => $name, 'error' => $e->getMessage()]);
            }
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Try multiple key variations and return the first non-empty value.
     */
    private function get(array $row, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (isset($row[$key]) && trim((string) $row[$key]) !== '') {
                return $row[$key];
            }
        }
        return null;
    }

    private function parseDate(mixed $value): ?string
    {
        if (empty($value)) return null;

        if (is_numeric($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)
                    ->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        $value = trim((string) $value);

        foreach (['d/m/Y', 'Y-m-d', 'm/d/Y', 'd-m-Y', 'Y/m/d', 'Ymd', 'd.m.Y'] as $format) {
            try {
                $date = \Carbon\Carbon::createFromFormat($format, $value);
                if ($date) return $date->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }
}
