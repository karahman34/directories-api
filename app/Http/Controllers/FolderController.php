<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Transformer;
use App\Http\Resources\FolderResource;
use App\Jobs\DeleteFolder;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    /**
     * Check wheater the folder exist or not.
     *
     * @param   int|string  $parent_folder_id
     * @param   int|string  $folder_name
     *
     * @return  bool
     */
    private function isFolderNameExist($parent_folder_id, $folder_name)
    {
        return Folder::where('parent_folder_id', $parent_folder_id)
                                ->where('name', $folder_name)
                                ->exists();
    }

    /**
     * Check if parent folder exist.
     *
     * @param   int|string  $parent_folder_id
     *
     * @return  bool
     */
    private function isParentFolderExist($parent_folder_id)
    {
        $storage = Auth::user()->storage;

        return Folder::where('id', $parent_folder_id)
                        ->where('storage_id', $storage->id)
                        ->exists();
    }

    /**
     * Create new folder.
     *
     * @param   Illuminate\Http\Request  $request
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $payload = $request->validate([
            'parent_folder_id' => 'required|integer',
            'name' => 'required|string|max:255'
        ]);

        try {
            if (!$this->isParentFolderExist($payload['parent_folder_id'])) {
                return Transformer::failed('Parent folder not found.', null, 404);
            }

            if ($this->isFolderNameExist($payload['parent_folder_id'], $payload['name'])) {
                return Transformer::failed('Name already exist.', null, 400);
            }

            $payload['storage_id'] = Auth::user()->storage->id;
            $folder = Folder::create($payload);

            return Transformer::success('Success to create folder.', new FolderResource($folder), 201);
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to create folder.');
        }
    }

    /**
     * Update folder.
     *
     * @param   Illuminate\Http\Request  $request
     * @param   Folder  $folder
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Folder $folder)
    {
        $payload = $request->validate([
            'parent_folder_id' => 'required|integer',
            'name' => 'required|string|max:255'
        ]);

        try {
            if (!$this->isParentFolderExist($payload['parent_folder_id'])) {
                return Transformer::failed('Parent folder not found.', null, 404);
            }

            if ($this->isFolderNameExist($payload['parent_folder_id'], $payload['name'])) {
                return Transformer::failed('Name already exist.', null, 400);
            }

            $folder->update($payload);

            return Transformer::success('Success to update folder.', new FolderResource($folder));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to update folder.');
        }
    }

    /**
     * Delete folder.
     *
     * @param   Folder  $folder
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function destroy(Folder $folder)
    {
        try {
            DeleteFolder::dispatchSync(Auth::user()->storage, $folder);

            return Transformer::success('Success to delete folder.');
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to delete folder.');
        }
    }
}