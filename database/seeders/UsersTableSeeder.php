<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();

        DB::table('users')->insert([
            'name' => 'John Doe',
            'username' => 'johndoe',
            'email' => '8K2E4@example.com',
            'password' => Hash::make('12345678'),
            'created_at' => $now,
            'updated_at' => $now
        ]);
    }
}
