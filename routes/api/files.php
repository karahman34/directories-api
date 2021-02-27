<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::prefix('files')->middleware('auth')->group(function () {
    Route::post('/', [FileController::class, 'store']);

    Route::delete('/{file}', [FileController::class, 'destroy']);
});
