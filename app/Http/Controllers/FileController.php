<?php

namespace App\Http\Controllers;

use App\Models\File;
use App\Models\Folder;
use App\Jobs\DeleteFiles;
use App\Events\FileCreated;
use App\Helpers\FileHelper;
use Illuminate\Http\Request;
use App\Http\Helpers\Transformer;
use App\Http\Resources\FileResource;
use App\Jobs\DecreaseParentFolderSize;
use App\Jobs\IncreaseParentFolderSize;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    /**
     * Get folder model.
     *
     * @param   string|int  $folder_id
     *
     * @return  Folder
     */
    private function getFolder($folder_id)
    {
        return Folder::owned()->select('id')->where('id', $folder_id)->first();
    }

    /**
     * Check storage space.
     *
     * @param   float  $size
     *
     * @return  bool
     */
    private function checkStorageSpace(float $size)
    {
        $storage = Auth::user()->storage()->select('space', 'used_space')->first();

        return $storage->used_space + $size > $storage->space
            ? false
            : true;
    }

    /**
     * Dispatching file event.
     *
     * @param  File  $file
     *
     * @return  void
     */
    private function emitFileCreatedEvent(File $file)
    {
        $storage = Auth()->user()->storage()->select('id')->first();

        event(new FileCreated($file, $storage));
    }

    /**
     * Store and save file.
     *
     * @param   Illuminate\Http\Request  $request
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'folder_id' => 'required|string',
            'file' => 'required|file|max:1024000'
        ]);

        try {
            $folder_id = null;
            if ($request->has('folder_id')) {
                $folder = $this->getFolder($request->input('folder_id'));

                if (!$folder) {
                    return Transformer::failed('Folder not found.', null, 404);
                } else {
                    $folder_id = $folder->id;
                }
            }
            
            // Prep file.
            $file_upload = $request->file('file');
            $file_name = FileHelper::formatFileName($folder_id, pathinfo($file_upload->getClientOriginalName(), PATHINFO_FILENAME));
            $file_extension = $file_upload->getClientOriginalExtension();
            $file_size = $file_upload->getSize();
            $file_mime_type = $file_upload->getMimeType();
            $folder_upload = File::$folder;

            // Check storage space.
            if (!$this->checkStorageSpace($file_size)) {
                return Transformer::failed('Storage is already hit the limit.', null, 403);
            }

            // Save file data.
            $file = File::create([
                'folder_id' => $folder_id,
                'path' => $file_upload->store($folder_upload),
                'name' => $file_name,
                'extension' => $file_extension,
                'size' => $file_size,
                'mime_type' => $file_mime_type,
            ]);

            $this->emitFileCreatedEvent($file);

            return Transformer::success('Success to upload file.', new FileResource($file), 201);
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to upload file.');
        }
    }

    /**
     * Copy file to another directory.
     *
     * @param   Request  $request
     * @param   File     $file
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function copy(Request $request, File $file)
    {
        $payload = $request->validate([
            'folder_id' => 'required|string'
        ]);

        try {
            // Check new folder.
            if (!$this->getFolder($payload['folder_id'])) {
                return Transformer::failed('Parent folder not found.', null, 404);
            }

            // Check storage space.
            if (!$this->checkStorageSpace($file->size)) {
                return Transformer::failed('Storage is already hit the limit.', null, 403);
            }

            // Copy file model.
            $copiedFile = FileHelper::copyFileModel($file, $payload['folder_id']);

            $this->emitFileCreatedEvent($copiedFile);

            return Transformer::success('Success to copy file.', new FileResource($copiedFile), 201);
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to copy file.');
        }
    }

    /**
     * Move file path.
     *
     * @param   Request  $request
     * @param   File     $file
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function move(Request $request, File $file)
    {
        $payload = $request->validate([
            'folder_id' => 'required|string'
        ]);

        try {
            // Check new folder.
            if (!$this->getFolder($payload['folder_id'])) {
                return Transformer::failed('Parent folder not found.', null, 404);
            }

            // Move file.
            $file = FileHelper::moveFileModel($file, $payload['folder_id']);

            return Transformer::success('Success to move file.', new FileResource($file));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to move file.');
        }
    }

    /**
     * Delete file.
     *
     * @param   string  $id
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function destroy(string $id)
    {
        try {
            $file = File::withTrashed()->findOrFail($id);
            $storage = Auth::user()->storage;

            if (!$file->trashed() && $file->folder_trashed === 'Y') {
                return Transformer::failed('You cannot delete file while the parent folder is deleted.', null, 403);
            }

            // Dispatch delete file job.
            DeleteFiles::dispatchSync($storage, collect([$file]));

            return Transformer::success('Success to delete file.');
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to delete file.');
        }
    }

    /**
    * Soft Delete file.
    *
    * @param   File  $file
    *
    * @return  Illuminate\Http\JsonResponse
    */
    public function softDestroy(File $file)
    {
        try {
            // Soft Delete file.
            $file->delete();

            // Decrease parents folder size.
            DecreaseParentFolderSize::dispatchSync($file->folder_id, $file->size);

            return Transformer::success('Success to soft delete file.');
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to soft delete file.');
        }
    }

    /**
    * Restore Deleted file.
    *
    * @param   string   $id
    *
    * @return  Illuminate\Http\JsonResponse
    */
    public function restore(string $id)
    {
        try {
            // Get the trashed file and restore it.
            $file = File::onlyTrashed()
                            ->where('id', $id)
                            ->where('folder_trashed', 'N')
                            ->firstOrFail();
            $file->restore();
                            
            // Increase parents folder size.
            IncreaseParentFolderSize::dispatchSync($file->folder_id, $file->size);

            return Transformer::success('Success to restore file.', new FileResource($file));
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to restore file.');
        }
    }
}
