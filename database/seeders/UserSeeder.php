<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::first();

        if (!$company) {
            $company = Company::factory()->create([
                'name' => 'CREDLY',
                'email' => 'info@credly.rw',
                'phone' => '0786748801',
                'address' => 'Default Address',
            ]);
        }

        User::create([
            'company_id' => $company->id,
            'name' => 'Admin User',
            'email' => 'uwayoben11@gmail.com',
            'phone' => '0786748001',
            'position' => 'Managing Director',
            'is_super_admin' => true,
            'password' => bcrypt('password'),
        ]);
    }
}
