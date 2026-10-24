<?php

namespace Database\Factories;

use App\Models\CelestialBody;
use App\Models\Mission;
use Illuminate\Database\Eloquent\Factories\Factory;

class MissionDestinationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mission_id' => Mission::factory(), 'celestial_body_id' => CelestialBody::factory(),
            'slug' => 'step-'.fake()->unique()->numerify('########'),
            'sequence' => 1, 'kind' => 'visit', 'objective' => 'Arrive at the observation point',
        ];
    }
}
