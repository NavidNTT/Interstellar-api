<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MissionDestination extends Model
{
    use HasFactory;

    protected $fillable = ['mission_id', 'celestial_body_id', 'slug', 'sequence', 'kind', 'objective'];

    protected function casts(): array
    {
        return ['sequence' => 'integer'];
    }

    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    public function celestialBody(): BelongsTo
    {
        return $this->belongsTo(CelestialBody::class);
    }
}
