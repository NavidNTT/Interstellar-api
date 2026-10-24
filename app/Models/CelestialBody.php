<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CelestialBody extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'name', 'type', 'short_description', 'full_description', 'diameter', 'mass', 'gravity', 'average_temperature', 'distance_from_sun', 'orbital_period', 'rotation_period', 'number_of_moons', 'number_of_planets', 'atmosphere', 'composition', 'color', 'scene_radius', 'scene_orbit_radius', 'interesting_facts', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'interesting_facts' => 'array', 'scene_radius' => 'float', 'scene_orbit_radius' => 'float'];
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(MissionDestination::class);
    }
}
