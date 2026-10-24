<?php

namespace Database\Seeders;

use App\Models\CelestialBody;
use App\Models\Mission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExplorationSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = json_decode(file_get_contents(database_path('seeders/data/exploration.json')), true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($catalog): void {
            foreach ($catalog['bodies'] as $body) {
                CelestialBody::query()->updateOrCreate(['slug' => $body['slug']], $body);
            }
            foreach ($catalog['missions'] as $definition) {
                $destinations = $definition['destinations'];
                unset($definition['destinations']);
                $mission = Mission::query()->updateOrCreate(['slug' => $definition['slug']], $definition);
                foreach ($destinations as $destination) {
                    $destination['celestial_body_id'] = CelestialBody::query()->where('slug', $destination['body_slug'])->sole()->id;
                    unset($destination['body_slug']);
                    $mission->destinations()->updateOrCreate(['slug' => $destination['slug']], $destination);
                }
            }
        });
    }
}
