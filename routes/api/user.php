<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('user')->middleware('auth')->group(function () {
    Route::get('/storage', [UserController::class, 'getStorage']);
});
