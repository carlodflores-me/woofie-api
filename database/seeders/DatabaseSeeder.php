<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Pet;
use Illuminate\Database\Eloquent\Factories\Factory;


class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // Create 5 users
        User::factory()->count(5)->create()->each(function ($user) {
            // For each user, create 5 pets
            Pet::factory()->count(5)->create([
                'user_id' => $user->id
            ]);
        });
    }
}
