<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Transformer;
use App\Http\Resources\StorageResource;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Get User Storage.
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function getStorage()
    {
        try {
            $storage = Auth::user()->storage;

            return Transformer::success('Success to get user storage.', new StorageResource($storage));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to get user storage.');
        }
    }
}
