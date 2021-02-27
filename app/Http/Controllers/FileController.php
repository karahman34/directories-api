<?php

namespace App\Http\Controllers;

use App\Http\Helpers\Transformer;
use App\Http\Resources\FileResource;
use App\Jobs\DeleteFiles;
use App\Models\File;
use App\Models\Folder;
use App\Models\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FileController extends Controller
{
    /**
     * Get folder model.
     *
     * @param   string|int  $storage_id
     * @param   string|int  $folder_id
     *
     * @return  Folder
     */
    private function getFolder($storage_id, $folder_id)
    {
        return Folder::select('id')->where('id', $folder_id)->where('storage_id', $storage_id)->first();
    }

    /**
     * Check storage space.
     *
     * @param   App\Models\Storage  $storage
     * @param   float  $size
     *
     * @return  bool
     */
    private function checkStorageSpace(Storage $storage, float $size)
    {
        return $storage->used_space + $size > $storage->space
            ? false
            : true;
    }

    /**
     * Format file name.
     *
     * @param   string|int  $folder_id
     * @param   string  $file_name
     *
     * @return  string
     */
    private function formatFileName($folder_id, string $file_name)
    {
        $file_exist = File::select('name')
                                ->where('folder_id', $folder_id)
                                ->where(function ($query) use ($file_name) {
                                    $query->whereRaw('SUBSTRING(name,1,CHAR_LENGTH(name) - 4) = ?', [$file_name])
                                            ->orWhere('name', $file_name);
                                })
                                ->orderByDesc('created_at')
                                ->limit(1)
                                ->first();

        if (!$file_exist) {
            return $file_name;
        }

        $last = ((int) substr($file_exist->name, -2, 1)) + 1;

        return "{$file_name} ({$last})";
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
            'folder_id' => 'required|integer',
            'file' => 'required|file|max:1024000'
        ]);

        try {
            $storage = Auth::user()->storage;
            $folder_id = null;
            if ($request->has('folder_id')) {
                $folder = $this->getFolder($storage->id, $request->input('folder_id'));

                if (!$folder) {
                    return Transformer::failed('Folder not found.', null, 404);
                } else {
                    $folder_id = $folder->id;
                }
            }
            
            // Prep file.
            $file_upload = $request->file('file');
            $file_name = $this->formatFileName($folder_id, pathinfo($file_upload->getClientOriginalName(), PATHINFO_FILENAME));
            $file_extension = $file_upload->getClientOriginalExtension();
            $file_size = $file_upload->getSize();
            $folder_upload = preg_replace('/[^a-zA-Z]+/', '_', Auth::user()->name);

            // Check storage space.
            if (!$this->checkStorageSpace($storage, $file_size)) {
                return Transformer::failed('Storage is already hit the limit.', null, 403);
            }

            // Save file data.
            $file = File::create([
                'folder_id' => $folder_id,
                'path' => $file_upload->store($folder_upload),
                'name' => $file_name,
                'extension' => $file_extension,
                'size' => $file_size,
            ]);

            // Increase storage space.
            Auth::user()->storage->increment('used_space', $file_size);

            return Transformer::success('Success to upload file.', new FileResource($file), 201);
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to upload file.');
        }
    }

    /**
     * Delete file.
     *
     * @param   File  $file
     *
     * @return  Illuminate\Http\JsonResponse
     */
    public function destroy(File $file)
    {
        try {
            // Dispatch delete file job.
            DeleteFiles::dispatchSync(Auth::user()->storage, collect([$file]));

            return Transformer::success('Success to delete file.', $file);
        } catch (\Throwable $th) {
            return Transformer::failed('Failed to delete file.');
        }
    }
}