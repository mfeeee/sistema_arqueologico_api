<?php

use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

Route::get('/test-queue', function () {
    \App\Jobs\TestarFilaJob::dispatch();
    return response()->json([
        'status' => 'dispatched',
        'time' => now()->toDateTimeString(),
    ]);
});

require __DIR__.'/settings.php';
