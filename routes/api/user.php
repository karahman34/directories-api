<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->middleware('auth')->group(function () {
    Route::get('/storage', [UserController::class, 'getStorage']);
    Route::get('/search', [UserController::class, 'search']);
    Route::get('/recent-uploads', [UserController::class, 'getRecentUploads']);

    Route::patch('/settings', [UserController::class, 'updateSettings']);

    Route::delete('/storage/batch-delete', [UserController::class, 'batchDelete']);
    Route::delete('/storage/soft-batch-delete', [UserController::class, 'softBatchDelete']);
});
