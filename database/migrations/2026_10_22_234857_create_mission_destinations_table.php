<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mission_destinations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('mission_id')->constrained()->cascadeOnDelete();
            $table->foreignId('celestial_body_id')->constrained()->restrictOnDelete();
            $table->string('slug');
            $table->unsignedInteger('sequence');
            $table->string('kind', 20);
            $table->text('objective');
            $table->timestamps();
            $table->unique(['mission_id', 'sequence']);
            $table->unique(['mission_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mission_destinations');
    }
};
