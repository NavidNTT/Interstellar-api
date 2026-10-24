<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CelestialBodyResource;
use App\Models\CelestialBody;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CelestialBodyController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CelestialBodyResource::collection(CelestialBody::query()->where('is_active', true)->orderBy('scene_orbit_radius')->get());
    }

    public function show(string $body): CelestialBodyResource
    {
        return new CelestialBodyResource(CelestialBody::query()->where('is_active', true)->where('slug', $body)->firstOrFail());
    }
}
