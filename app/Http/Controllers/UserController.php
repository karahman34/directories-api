<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Transformer;
use App\Http\Resources\FilesCollection;
use App\Http\Resources\SearchResultsCollection;
use App\Http\Resources\StorageResource;
use App\Http\Resources\UserSettingsResource;
use App\Jobs\DecreaseParentFolderSize;
use App\Jobs\DeleteFiles;
use App\Jobs\DeleteFolder;
use App\Jobs\SoftDeleteFolder;
use App\Models\File;
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
                        ->selectRaw('files.id, files.folder_id, files.name, path, files.size, extension, mime_type, folder_trashed, files.deleted_at, files.created_at, files.updated_at')
                        ->join('folders', 'files.folder_id', 'folders.id')
                        ->where('folders.storage_id', $storage->id)
                        ->where('files.name', 'like', '%'.$q.'%')
                        ->where('folder_trashed', 'N')
                        ->whereNull('files.deleted_at')
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
                            ->whereNull('files.deleted_at')
                            ->orderByDesc('files.created_at')
                            ->limit(16)
                            ->get();

            return Transformer::success('Success to get recent upload files.', new FilesCollection($files));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to get recent upload files.');
        }
    }

    /**
     * Update user settings.
     *
     * @param   Request  $request
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function updateSettings(Request $request)
    {
        $payload = $request->validate([
            'trash' => 'required|string|in:enable,disable'
        ]);

        try {
            $user = Auth::user();
            $user->settings()->update($payload);

            return Transformer::success('Success to update user settings.', new UserSettingsResource($user->settings));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to update user settings.');
        }
    }

    /**
     * Batch delete files & folders.
     *
     * @param   Request  $request
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function batchDelete(Request $request)
    {
        $payload = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string',
            'parent_folder_id' => 'required|string'
        ]);

        try {
            $storage = Auth::user()->storage;
            $files = DB::table('files')
                            ->select('files.*')
                            ->join('folders', 'folders.id', 'files.folder_id')
                            ->join('storages', 'storages.id', 'folders.storage_id')
                            ->where('storages.user_id', Auth::id())
                            ->where('files.folder_id', $payload['parent_folder_id'])
                            ->whereIn('files.id', $payload['ids'])
                            ->get();

            $folders = Folder::owned()
                                ->where('parent_folder_id', $payload['parent_folder_id'])
                                ->whereIn('id', $payload['ids'])
                                ->get();

            // Delete files job.
            DeleteFiles::dispatchSync($storage, $files);

            // Delete folder job.
            foreach ($folders as $folder) {
                DeleteFolder::dispatchSync($storage, $folder);
            }

            return Transformer::success('Success to run batch delete.', $files->merge($folders));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to run batch delete.');
        }
    }

    /**
     * Run soft batch delete.
     *
     * @param   Request  $request
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function softBatchDelete(Request $request)
    {
        $payload = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'string',
            'parent_folder_id' => 'required|string',
        ]);

        try {
            $folders = Folder::owned()
                    ->select('id', 'size')
                    ->whereIn('id', $payload['ids'])
                    ->where('parent_folder_id', $payload['parent_folder_id'])
                    ->get();
                                
            $files = File::select('files.id', 'files.size')
                    ->join('folders', 'folders.id', 'files.folder_id')
                    ->join('storages', 'storages.id', 'folders.storage_id')
                    ->where('storages.user_id', Auth::id())
                    ->where('folder_id', $payload['parent_folder_id'])
                    ->whereIn('files.id', $payload['ids'])
                    ->get();

            // Delete folders.
            $folders->each(function (Folder $folder) {
                SoftDeleteFolder::dispatchSync($folder);
            });

            // Delete files.
            File::whereIn('id', $files->pluck('id'))->delete();
                    
            // Decrease parents folder size.
            $size_decrease = $folders->sum('size') + $files->sum('size');
            DecreaseParentFolderSize::dispatchSync($payload['parent_folder_id'], $size_decrease);
                
            return Transformer::success('Success to run soft batch delete.');
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to run batch delete.');
        }
    }
}
