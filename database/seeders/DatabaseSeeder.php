<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        $this->call([MenuSeeder::class, MatherialSeeder::class, IngredientSeeder::class, FixedItemsSeeder::class, ClientSeeder::class, EmployeesCostSeeder
        ::class, InformationSeeder::class, EventSeeder::class]);
    }
}
