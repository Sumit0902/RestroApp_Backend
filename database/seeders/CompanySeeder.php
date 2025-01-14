<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $companies = [
            [
                'company_name' => 'Savory Delights',
                'company_about' => 'Serving a variety of gourmet dishes with an international twist, catering to all taste buds.',
                'company_address1' => '123 Flavor Street',
                'company_city' => 'London',
                'company_state' => 'England',
                'company_zip' => 'SW1A 1AA',
                'phone' => '+44 20 1234 5678',
                'email' => 'savorydelights@grr.la',
                'logo' => null, // or you can add path if you have logo
            ],
            [
                'company_name' => 'Urban Eats',
                'company_about' => 'A bustling food court offering an eclectic mix of street food from around the world.',
                'company_address1' => '456 Tasty Avenue',
                'company_city' => 'Manchester',
                'company_state' => 'England',
                'company_zip' => 'M1 1AE',
                'phone' => '+44 161 234 5678',
                'email' => 'urbaneats@grr.la',
                'logo' => null,
            ],
            [
                'company_name' => 'Spice Haven',
                'company_about' => 'Specializes in aromatic and flavorful dishes inspired by the rich culinary traditions of South Asia.',
                'company_address1' => '789 Spice Road',
                'company_city' => 'Birmingham',
                'company_state' => 'England',
                'company_zip' => 'B1 1AA',
                'phone' => '+44 121 234 5678',
                'email' => 'spicehaven@grr.la',
                'logo' => null,
            ],
            [
                'company_name' => 'Fresh Feast',
                'company_about' => 'Known for its fresh, farm-to-table approach, offering a healthy and delicious dining experience.',
                'company_address1' => '321 Greenway Blvd',
                'company_city' => 'Leeds',
                'company_state' => 'England',
                'company_zip' => 'LS1 1AA',
                'phone' => '+44 113 234 5678',
                'email' => 'freshfeast@grr.la',
                'logo' => null,
            ],
            [
                'company_name' => 'Ocean Breeze Bistro',
                'company_about' => 'A coastal-themed restaurant offering the finest seafood and refreshing beverages.',
                'company_address1' => '654 Seaside Lane',
                'company_city' => 'Brighton',
                'company_state' => 'England',
                'company_zip' => 'BN1 1AA',
                'phone' => '+44 1273 234 5678',
                'email' => 'oceanbreeze@grr.la',
                'logo' => null,
            ],
        ];

        foreach ($companies as $company) {
            Company::create($company);
        }
    }
}
