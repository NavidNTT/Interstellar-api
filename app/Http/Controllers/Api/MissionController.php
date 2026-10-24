<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MissionResource;
use App\Models\Mission;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class MissionController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return MissionResource::collection(Mission::query()->where('is_active', true)->with('destinations.celestialBody')->orderBy('id')->get());
    }

    public function show(string $mission): MissionResource
    {
        return new MissionResource(Mission::query()->where('is_active', true)->where('slug', $mission)->with('destinations.celestialBody')->firstOrFail());
    }
}
