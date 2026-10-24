<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CelestialBodyFactory extends Factory
{
    public function definition(): array
    {
        $catalog = json_decode(file_get_contents(database_path('seeders/data/exploration.json')), true, 512, JSON_THROW_ON_ERROR);

        return [...$catalog['bodies'][1], 'slug' => 'body-'.fake()->unique()->numerify('########')];
    }
}
