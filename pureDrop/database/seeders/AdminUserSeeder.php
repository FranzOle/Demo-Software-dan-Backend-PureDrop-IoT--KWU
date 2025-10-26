<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seed.
     */
    public function run(): void
    {
        $email = 'admin@puredrop.unesa.ac.id';
        $password = 'admin123';

        User::updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Admin PureDrop',
                'role' => 'admin',
                'password' => Hash::make($password),
            ]
        );
    }
}
