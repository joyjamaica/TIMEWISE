<?php

use App\Http\Controllers\Collaborate\CollaborateController;
use App\Http\Controllers\Collaborate\TaskController;
use App\Http\Controllers\Collaborate\TeamController;
use App\Http\Controllers\Collaborate\TeamResourceController;
use Illuminate\Support\Facades\Route;

// Loaded from routes/web.php with:  require __DIR__.'/collaborate.php';
Route::middleware('auth')->prefix('collaborate')->name('collaborate.')->group(function () {
    Route::get('/', [CollaborateController::class, 'index'])->name('index');

    // Static segments must come before {team}.
    Route::get('teams/create', [TeamController::class, 'create'])->name('teams.create');
    Route::post('teams', [TeamController::class, 'store'])->name('teams.store');
    Route::get('teams/join', [TeamController::class, 'joinForm'])->name('teams.join');
    Route::post('teams/join', [TeamController::class, 'join'])->name('teams.join.store');
    Route::get('teams/{team}', [TeamController::class, 'show'])->name('teams.show');

    Route::scopeBindings()->prefix('teams/{team}')->group(function () {
        Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::put('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
        Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
        Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

        Route::post('resources', [TeamResourceController::class, 'store'])->name('resources.store');
        Route::get('resources/{resource}', [TeamResourceController::class, 'download'])->name('resources.download');
        Route::delete('resources/{resource}', [TeamResourceController::class, 'destroy'])->name('resources.destroy');
    });
});
