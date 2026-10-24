<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;
use UnexpectedValueException;

class CelestialBodyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fields = ['slug', 'name', 'type', 'short_description', 'full_description', 'diameter', 'mass', 'gravity', 'average_temperature', 'distance_from_sun', 'orbital_period', 'rotation_period', 'number_of_moons', 'number_of_planets', 'atmosphere', 'composition', 'color', 'scene_radius', 'scene_orbit_radius', 'interesting_facts'];
        $data = $this->resource->only($fields);
        $rules = array_fill_keys(array_diff($fields, ['scene_radius', 'scene_orbit_radius', 'interesting_facts']), 'required|string');
        $rules['slug'] = ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];
        $rules['type'] = 'required|in:star,planet';
        $rules['color'] = ['required', 'regex:/^#[0-9a-fA-F]{6}$/'];
        $rules['scene_radius'] = 'required|numeric|gt:0|max:10';
        $rules['scene_orbit_radius'] = 'required|numeric|min:0|max:100';
        $rules['interesting_facts'] = 'required|array|min:1';
        $rules['interesting_facts.*'] = 'required|string';
        if (Validator::make($data, $rules)->fails()) {
            throw new UnexpectedValueException('Invalid celestial body catalog record.');
        }

        return $data;
    }
}
