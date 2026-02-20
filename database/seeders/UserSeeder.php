<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserMeta;
use App\Models\UserProfile;
use App\Models\UserProfileUrl;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Ashish Khawla',
            'email' => 'khawla@fanaticcoders.com',
            'password' => Hash::make('khawla@123#'),
            'role' => 'super_admin',
            'avatar' => 1,
        ]);

        User::factory()->create([
            'name' => 'Akshay Chauhan',
            'email' => 'akshay@fanaticcoders.com',
            'password' => Hash::make('akshay@123#'),
            'role' => 'admin',
            'avatar' => 2,
        ]);

        // Creating Multiple Customer Users
        $customers = [
            // ['name' => 'Akshay Chauhan', 'email' => 'akshay@fanaticcoders.com', 'password' => 'akshay@123#'],
            ['name' => 'Vishal Sandhu', 'email' => 'vishal@fanaticcoders.com', 'password' => 'vishal@123#'],
            ['name' => 'Atul Sharma', 'email' => 'atul@fanaticcoders.com', 'password' => 'atul@123#'],
            ['name' => 'Gurmeet Singh', 'email' => 'gurmeet@fanaticcoders.com', 'password' => 'gurmeet@123#'],
            ['name' => 'Abhinav Chaudhary', 'email' => 'abhinav@fanaticcoders.com', 'password' => 'abhinav@123#'],
            ['name' => 'Vikas Dhiman', 'email' => 'vikas@fanaticcoders.com', 'password' => 'vikas@123#'],
            ['name' => 'Anshul Guleria', 'email' => 'anshul@fanaticcoders.com', 'password' => 'anshul@123#'],
        ];

        foreach ($customers as $customer) {
            User::factory()->create([
                'name' => $customer['name'],
                'email' => $customer['email'],
                'password' => Hash::make($customer['password']),
                'role' => 'customer',
                'avatar' => rand(1, 5),
            ]);
        }
    }
}
