<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'firstname' => 'Super',
            'lastname' => 'Admin',
            'email' => 'superadmin@restro.com',
            'password' => Hash::make('Pi314159265'), // Replace 'securepassword' with the desired password
            'role' => 'superadmin', // Assuming 'role' is a string, and 'admin' is the role for administrators
        ]);
    }
}
