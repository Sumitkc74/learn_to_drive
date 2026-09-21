<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Hash;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (User::where('email', 'admin@admin.com')->exists()) return;
        $password = config('seed-admin.password');
        if (!$password && !app()->environment('testing')) {
            $this->command?->warn('Set SEED_ADMIN_PASSWORD before creating the initial administrator.');
            return;
        }
        User::create(
            [
                'email' => 'admin@admin.com',
                'name' => 'Admin',
                'password' => Hash::make($password ?: \Illuminate\Support\Str::random(40)),
                'role' => 'Admin',
                'is_seed_admin' => true,
                'phoneNumber' => '9800000000',
                'profileImage' => 'dist/img/avatar-160x160.png',
            ]
        );
    }
}
