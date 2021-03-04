<?php

namespace App\Http\Controllers;

use App\Helpers\CopyFolderHelper;
use App\Helpers\FolderHelper;
use App\Http\Helpers\Transformer;
use App\Http\Resources\FolderResource;
use App\Http\Resources\WithSubFolderResource;
use App\Jobs\DecreaseParentFolderSize;
use App\Jobs\DeleteFolder;
use App\Jobs\IncreaseParentFolderSize;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FolderController extends Controller
{
    /**
     * Get root folder.
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function getRootFolder()
    {
        try {
            $root = Folder::owned()
                            ->whereNull('parent_folder_id')
                            ->with('sub_folders', 'files')
                            ->first();

            return Transformer::success('Success to get root folder.', new WithSubFolderResource($root));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to get root folder.');
        }
    }

    /**
     * Get folder with the sub folders and files.
     *
     * @param   string  $folder_id
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function show($folder_id)
    {
        try {
            $folder = Folder::owned()
                                ->where('id', $folder_id)
                                ->with('sub_folders', 'files')
                                ->first();

            return Transformer::success('Success to get folder details.', new WithSubFolderResource($folder));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to get folder details.');
        }
    }

    /**
     * Check wheater the folder exist or not.
     *
     * @param   string  $parent_folder_id
     * @param   string  $folder_name
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
     * @param   string  $parent_folder_id
     *
     * @return  bool
     */
    private function isParentFolderExist($parent_folder_id)
    {
        return Folder::owned()
                        ->where('id', $parent_folder_id)
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
            'parent_folder_id' => 'required|string',
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
            'name' => 'required|string|max:255'
        ]);

        try {
            if ($this->isFolderNameExist($folder->parent_folder_id, $payload['name'])) {
                return Transformer::failed('Name already exist.', null, 400);
            }

            $folder->update([
                'name' => $payload['name'],
            ]);

            return Transformer::success('Success to update folder.', new FolderResource($folder));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to update folder.');
        }
    }

    /**
     * Checke if the storage is full.
     *
     * @param   float  $size
     *
     * @return  bool
     */
    private function isStorageFull(float $size)
    {
        $storage = Auth::user()->storage()->select('space', 'used_space')->first();

        return $size + $storage->used_space > $storage->space
            ? true
            : false;
    }

    /**
     * Copy folder.
     *
     * @param   Request  $request
     * @param   Folder   $folder
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function copy(Request $request, Folder $folder)
    {
        $payload = $request->validate([
            'parent_folder_id' => 'required|string',
        ]);

        try {
            if ($folder->isRoot()) {
                return Transformer::failed('You cannot copy root folder.', null, 403);
            }

            if ($this->isStorageFull($folder->size)) {
                return Transformer::failed('Storage is already hit the limit.', null, 400);
            }

            // Deeply copy folder.
            $new_folder = CopyFolderHelper::set($folder, $payload['parent_folder_id'])->copy();

            // Increase storage used space.
            Auth::user()->storage()->increment('used_space', $folder->size);

            // Increase parents folder size.
            IncreaseParentFolderSize::dispatch($payload['parent_folder_id'], $folder->size);

            return Transformer::success('Success to copy folder.', new FolderResource($new_folder), 201);
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to copy folder.', $th->getLine());
        }
    }

    /**
     * Move folder.
     *
     * @param   Request  $request
     * @param   Folder   $folder
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function move(Request $request, Folder $folder)
    {
        $payload = $request->validate([
            'parent_folder_id' => 'required|string'
        ]);

        try {
            if ($folder->isRoot()) {
                return Transformer::failed('You cannot move root folder.', null, 403);
            }

            if (!$this->isParentFolderExist($payload['parent_folder_id'])) {
                return Transformer::failed('Parent folder not found.', null, 404);
            }

            $old_parent_id = $folder->parent_folder_id;
            $folder_name = FolderHelper::formatFolderName($folder->name, $payload['parent_folder_id']);

            $folder->update([
                'name' => $folder_name,
                'parent_folder_id' => $payload['parent_folder_id']
            ]);

            DecreaseParentFolderSize::dispatchSync($old_parent_id, $folder->size);
            IncreaseParentFolderSize::dispatchSync($folder->parent_folder_id, $folder->size);

            return Transformer::success('Success to move folder.', new FolderResource($folder));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to move folder.');
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
            if ($folder->isRoot()) {
                return Transformer::failed('You cannot delete root folder.', null, 403);
            }

            DeleteFolder::dispatchSync(Auth::user()->storage, $folder);

            return Transformer::success('Success to delete folder.');
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to delete folder.');
        }
    }
}
