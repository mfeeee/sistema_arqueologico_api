<?php

use App\Jobs\TestarFilaJob;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Sistema Arqueologico API',
        'status' => 'ok',
    ]);
});

Route::get('/test-queue', function () {
    TestarFilaJob::dispatch();

    return response()->json([
        'status' => 'dispatched',
        'time' => now()->toDateTimeString(),
    ]);
});
