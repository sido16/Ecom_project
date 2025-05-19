<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
{
    User::create([
        'full_name' => 'Hadj Ben',
        'email' => 'hadj@gmail.com',
        'password' => Hash::make('Pass1234'),
        'phone_number' => '0555123456',
        'address' => '123 Algiers St, Algiers, Algeria',
        'role' => 'user',
        'email_verified_at' => now(),
    ]);

    User::create([
        'full_name' => 'Ahmed Ben',
        'email' => 'ahmed@gmail.com',
        'password' => Hash::make('Pass1234'),
        'phone_number' => '0554123456',
        'address' => '123 Algiers St, Algiers, Algeria',
        'role' => 'user',
        'email_verified_at' => now(),
    ]);

    User::create([
        'full_name' => 'Sami Ben',
        'email' => 'sami@gmail.com',
        'password' => Hash::make('Pass1234'),
        'phone_number' => '0550123456',
        'address' => '123 Algiers St, Algiers, Algeria',
        'role' => 'user',
        'email_verified_at' => now(),
    ]);

    User::create([
        'full_name' => 'Youssef Ben',
        'email' => 'youssef@gmail.com',
        'password' => Hash::make('Pass1234'),
        'phone_number' => '0555323456',
        'address' => '123 Algiers St, Algiers, Algeria',
        'role' => 'user',
        'email_verified_at' => now(),
    ]);
}
}