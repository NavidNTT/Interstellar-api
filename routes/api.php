<?php

use App\Http\Controllers\Api\CelestialBodyController;
use App\Http\Controllers\Api\MissionController;
use Illuminate\Support\Facades\Route;

// Legacy aliases remain available for existing clients. Both versions use the same Resources.
foreach (['' => '', 'v1' => 'v1.'] as $prefix => $name) {
    Route::prefix($prefix)->name($name)->group(function (): void {
        Route::get('celestial-bodies', [CelestialBodyController::class, 'index'])->name('bodies.index');
        Route::get('celestial-bodies/{body}', [CelestialBodyController::class, 'show'])->where('body', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('bodies.show');
        Route::get('missions', [MissionController::class, 'index'])->name('missions.index');
        Route::get('missions/{mission}', [MissionController::class, 'show'])->where('mission', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('missions.show');
    });
}
