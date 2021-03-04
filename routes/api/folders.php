<?php

use App\Http\Controllers\FolderController;
use Illuminate\Support\Facades\Route;

Route::prefix('folders')->middleware('auth')->group(function () {
    Route::get('/root', [FolderController::class, 'getRootFolder']);
    Route::get('/{folder_id}', [FolderController::class, 'show']);

    Route::post('/', [FolderController::class, 'store']);
    Route::post('/{folder}/copy', [FolderController::class, 'copy']);
    Route::post('/{folder}/move', [FolderController::class, 'move']);

    Route::patch('/{folder}', [FolderController::class, 'update']);
    Route::patch('/{id}/restore', [FolderController::class, 'restore']);

    Route::delete('/{id}', [FolderController::class, 'destroy']);
    Route::delete('/{folder}/soft', [FolderController::class, 'softDestroy']);
});
