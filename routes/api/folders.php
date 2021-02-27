<?php

use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

Route::prefix('folders')->middleware('auth')->group(function () {
    Route::post('/', [FolderController::class, 'store']);

    Route::patch('/{folder}', [FolderController::class, 'update']);

    Route::delete('/{folder}', [FolderController::class, 'destroy']);
});
