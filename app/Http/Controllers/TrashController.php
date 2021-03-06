<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Transformer;
use App\Http\Resources\TrashDirectoriesCollection;
use App\Http\Resources\WithSubFolderResource;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Support\Facades\Auth;

class TrashController extends Controller
{
    /**
     * Get trash directories list.
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $folders = Folder::onlyTrashed()->owned()->with(['files', 'sub_folders'])->get();
            $files = File::onlyTrashed()
                            ->select('files.*')
                            ->join('folders', 'folders.id', 'files.folder_id')
                            ->join('storages', 'storages.id', 'folders.storage_id')
                            ->where('storages.user_id', Auth::id())
                            ->get();

            return Transformer::success('Success to get trash directories.', new TrashDirectoriesCollection($folders, $files));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to get trash directories.');
        }
    }

    /**
     * Get Folder detail.
     *
     * @param   string  $id
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function show(string $id)
    {
        try {
            $folder = Folder::withTrashed()
                                ->owned()
                                ->where('id', $id)
                                ->with(['files', 'sub_folders'])
                                ->first();

            return Transformer::success('Success to get folder detail.', new WithSubFolderResource($folder));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to get folder detail.');
        }
    }
}
