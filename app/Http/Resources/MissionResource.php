<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;
use UnexpectedValueException;

class MissionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = $this->resource->only(['slug', 'title', 'description', 'objective', 'category', 'difficulty', 'estimated_duration', 'completion_label']);
        $rules = array_fill_keys(array_keys($data), 'required|string');
        $rules['slug'] = ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'];
        $rules['category'] = 'required|in:observation,travel,science';
        $rules['difficulty'] = 'required|in:introductory,standard';
        if (Validator::make($data, $rules)->fails() || $this->destinations->isEmpty()) {
            throw new UnexpectedValueException('Invalid mission catalog record.');
        }
        $expectedSequence = 1;
        foreach ($this->destinations as $destination) {
            if ($destination->sequence !== $expectedSequence++) {
                throw new UnexpectedValueException('Invalid mission itinerary order.');
            }
        }
        $data['destinations'] = MissionDestinationResource::collection($this->destinations)->resolve($request);

        return $data;
    }
}
