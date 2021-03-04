<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::prefix('files')->middleware('auth')->group(function () {
    Route::post('/', [FileController::class, 'store']);

    Route::post('/{file}/copy', [FileController::class, 'copy']);
    Route::post('/{file}/move', [FileController::class, 'move']);
    
    Route::patch('/{id}/restore', [FileController::class, 'restore']);

    Route::delete('/{id}', [FileController::class, 'destroy']);
    Route::delete('/{file}/soft', [FileController::class, 'softDestroy']);
});
