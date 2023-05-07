<?php

namespace Database\Factories;

use App\Models\Pet;
use Illuminate\Database\Eloquent\Factories\Factory;

class PetFactory extends Factory
{
    protected $model = Pet::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name(),
            'age' => $this->faker->numberBetween(1, 20),
            'breed' => $this->faker->word(),
            'species' => $this->faker->randomElement(['cat', 'dog', 'bird', 'fish']),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'color' => $this->faker->colorName(),
            'description' => $this->faker->paragraph(),
            'user_id' => function () {
                return \App\Models\User::factory()->create()->id;
            },
        ];
    }
}