<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Validator;
use UnexpectedValueException;

class MissionDestinationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $body = $this->celestialBody;
        if (! $body || ! $body->is_active) {
            throw new UnexpectedValueException('Unavailable mission destination.');
        }
        $data = ['slug' => $this->slug, 'body_slug' => $body->slug, 'sequence' => $this->sequence, 'kind' => $this->kind, 'objective' => $this->objective];
        if (Validator::make($data, [
            'slug' => ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'body_slug' => ['required', 'string', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'sequence' => 'required|integer|min:1', 'kind' => 'required|in:visit,inspect', 'objective' => 'required|string',
        ])->fails()) {
            throw new UnexpectedValueException('Invalid mission destination record.');
        }

        return $data;
    }
}
