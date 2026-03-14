<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProfilSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@gmail.com'],
            [
                'name' => 'Admin',
                'password' => bcrypt('123456'),
            ]
        );

        // Staff::firstOrCreate(
        //     ['email' => 'staff@genesacorp.com'],
        //     [
        //         'name' => 'Staff',
        //         'password' => bcrypt('Genesacorp12345'),
        //     ]
        // );

        // Employee::firstOrCreate(
        //     ['email' => 'afyww18@gmail.com'],
        //     [
        //         'name' => 'Afy Wahyu',
        //         'password' => bcrypt('123456'),
        //     ]
        // );

    }
}
