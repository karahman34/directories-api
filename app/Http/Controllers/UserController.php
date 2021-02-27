<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Transformer;
use App\Http\Resources\FilesCollection;
use App\Http\Resources\StorageResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

    /**
     * Get recent upload files.
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function getRecentUploads()
    {
        try {
            $files = DB::table('files')
                            ->select('files.*')
                            ->join('folders', 'files.folder_id', 'folders.id')
                            ->join('storages', 'folders.storage_id', 'storages.id')
                            ->where('folders.storage_id', Auth::id())
                            ->orderByDesc('files.created_at')
                            ->limit(8)
                            ->get();

            return Transformer::success('Success to get recent upload files.', new FilesCollection($files));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to get recent upload files.');
        }
    }
}
