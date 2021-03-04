<?php

use App\Http\Controllers\TrashController;
use Illuminate\Support\Facades\Route;

Route::prefix('trash')->middleware('auth')->group(function () {
    Route::get('/', [TrashController::class, 'index']);

    Route::get('/{folder}', [TrashController::class, 'show']);
});
