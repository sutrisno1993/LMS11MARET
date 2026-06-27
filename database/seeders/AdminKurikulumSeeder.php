<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AdminKurikulumSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Membuat atau memperbarui admin dengan email dan password yang diminta
        User::updateOrCreate(
            ['email' => 'admin2@smk11maret.sch.id'], 
            [
                'name' => 'Admin Kurikulum Super',
                'password' => Hash::make('Sutrisno_123'),
                'role' => 'ADMIN',
            ]
        );
    }
}
