<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        \App\Models\User::create([
            'name' => 'Super Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'role' => 'super_admin',
        ]);

        \App\Models\User::create([
            'name' => 'Médico Prueba',
            'email' => 'doctor@admin.com',
            'password' => Hash::make('password'),
            'role' => 'doctor',
        ]);

        \App\Models\User::create([
            'name' => 'Recepción',
            'email' => 'reception@admin.com',
            'password' => Hash::make('password'),
            'role' => 'receptionist',
        ]);

        \App\Models\User::create([
            'name' => 'Farmacéutico',
            'email' => 'farmacia@admin.com',
            'password' => Hash::make('password'),
            'role' => 'pharmacist',
        ]);
    }
}
