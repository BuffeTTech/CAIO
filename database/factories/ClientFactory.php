<?php

namespace Database\Factories;

use App\Models\Address;
use Illuminate\Database\Eloquent\Factories\Factory;
use Faker\Provider\pt_BR\Person;
use Faker\Factory as Faker;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $faker = Faker::create('pt_BR');
        $faker->addProvider(new Person($faker));

        return [
            'name' => $faker->name(),
            'email' => fake()->unique()->safeEmail(),
            'whatsapp'=> $faker->cellphoneNumber(),
            'address_id' => Address::factory()->create()
        ];
    }
}
