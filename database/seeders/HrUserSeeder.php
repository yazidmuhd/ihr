<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class HrUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'humanres'],
            [
                'name'     => 'HR Staff',
                'username' => 'humanres',
                'email'    => 'humanres@ihr.local', 
                'password' => Hash::make('admin01'),
                'is_hr'    => true,
            ]
        );
    }
}
