<?php

use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MatrixController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PulseBotController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/collaborate', function () {
    return view('collaborate.index', [
        'teams' => collect(),
    ]);
})->name('collaborate.index');

require __DIR__.'/auth.php';

Route::get('/auth/google', [
    GoogleController::class,
    'redirectToGoogle'
])->name('google.login');

Route::get('/auth/google/callback', [
    GoogleController::class,
    'handleGoogleCallback'
])->name('google.callback');

require __DIR__.'/collaborate.php';

// Route to view the Matrix page
Route::get('/matrix', [MatrixController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('matrix.index');

// Route to save a new task
Route::middleware('auth')->group(function () {
    Route::post('/tasks', [MatrixController::class, 'store'])->name('tasks.store');
    Route::put('/tasks/{id}', [MatrixController::class, 'update'])->name('tasks.update');
    Route::patch('/tasks/{id}/complete', [MatrixController::class, 'toggleComplete'])->name('tasks.complete');
});

// PulseBot AI assistant
Route::middleware(['auth', 'verified'])->prefix('pulsebot')->name('pulsebot.')->group(function () {
    Route::get('/', [PulseBotController::class, 'index'])->name('index');
    Route::post('/send', [PulseBotController::class, 'send'])->middleware('throttle:10,1')->name('send');
    Route::post('/clear', [PulseBotController::class, 'clear'])->name('clear');
});