<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
   // database/seeders/DatabaseSeeder.php
public function run(): void
{
    // Create a default company first
    $company = \App\Models\Company::create([
        'name'  => 'CEDLYRW',
        'email' => 'info@credly.rw',
        'phone' => '0786748801',
        'address' => 'Kigali, Rwanda',
    ]);

    // Then create the super admin user
    \App\Models\User::create([
        'company_id'     => $company->id,
        'name'           => 'Super Admin',
        'email'          => 'uwayoben11@gmail.com',
        'password'       => bcrypt('admin'),
        'is_super_admin' => true,
    ]);
}
}
