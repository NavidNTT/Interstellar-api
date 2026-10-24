<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mission extends Model
{
    use HasFactory;

    protected $fillable = ['slug', 'title', 'description', 'objective', 'category', 'difficulty', 'estimated_duration', 'completion_label', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(MissionDestination::class)->orderBy('sequence');
    }
}
