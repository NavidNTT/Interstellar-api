<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('celestial_bodies', function (Blueprint $table): void {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('type', 20);
            $table->text('short_description');
            $table->text('full_description');
            foreach (['diameter', 'mass', 'gravity', 'average_temperature', 'distance_from_sun', 'orbital_period', 'rotation_period', 'number_of_moons', 'number_of_planets'] as $field) {
                $table->string($field);
            }
            $table->text('atmosphere');
            $table->text('composition');
            $table->string('color', 7);
            $table->double('scene_radius');
            $table->double('scene_orbit_radius');
            $table->json('interesting_facts');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('celestial_bodies');
    }
};
