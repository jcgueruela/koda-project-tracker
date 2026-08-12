<?php

declare(strict_types=1);

use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth'])->group(function () {
    Route::inertia('dashboard', 'projects/Index')->name('dashboard');

    Route::prefix('api')->group(function () {
        Route::apiResource('projects', ProjectController::class);
    });
});

require __DIR__.'/settings.php';
