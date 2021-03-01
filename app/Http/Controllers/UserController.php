<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Transformer;
use App\Http\Resources\FilesCollection;
use App\Http\Resources\SearchResultsCollection;
use App\Http\Resources\StorageResource;
use App\Models\Folder;
use Illuminate\Http\Request;
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
     * Search folders & files.
     *
     * @param   Request  $request
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string'
        ]);

        try {
            $q = $request->input('q');
            $storage = Auth::user()->storage()->select('id')->first();
            $folders = Folder::owned()->where('name', 'like', '%'.$q.'%')->get();
            $files = DB::table('files')
                        ->selectRaw('files.id, files.folder_id, files.name, path, files.size, extension, mime_type, files.created_at, files.updated_at')
                        ->join('folders', 'files.folder_id', 'folders.id')
                        ->where('folders.storage_id', $storage->id)
                        ->where('files.name', 'like', '%'.$q.'%')
                        ->get();

            return Transformer::success('Success to get search results.', new SearchResultsCollection($folders, $files));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to get search results.');
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
