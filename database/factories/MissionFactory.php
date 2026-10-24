<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MissionFactory extends Factory
{
    public function definition(): array
    {
        $catalog = json_decode(file_get_contents(database_path('seeders/data/exploration.json')), true, 512, JSON_THROW_ON_ERROR);
        $definition = $catalog['missions'][0];
        unset($definition['destinations']);

        return [...$definition, 'slug' => 'mission-'.fake()->unique()->numerify('########')];
    }
}
