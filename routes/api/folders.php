<?php

use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

Route::prefix('folders')->middleware('auth')->group(function () {
    Route::get('/root', [FolderController::class, 'getRootFolder']);
    Route::get('/{folder_id}', [FolderController::class, 'show']);

    Route::post('/', [FolderController::class, 'store']);

    Route::patch('/{folder}', [FolderController::class, 'update']);

    Route::delete('/{folder}', [FolderController::class, 'destroy']);
});
