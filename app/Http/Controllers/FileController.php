<?php

namespace App\Http\Controllers;

use App\Events\FileCreated;
use App\Http\Helpers\Transformer;
use App\Http\Resources\FileResource;
use App\Jobs\DeleteFiles;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Http\Request;
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
     * Format file name.
     *
     * @param   string  $folder_id
     * @param   string  $file_name
     *
     * @return  string
     */
    private function formatFileName($folder_id, string $file_name)
    {
        $file = File::selectRaw('SUBSTRING(name, -2, 1) + 1 AS next')
                                ->where('folder_id', $folder_id)
                                ->where(function ($query) use ($file_name) {
                                    $query->where(function ($query2) use ($file_name) {
                                        $query2->whereRaw('SUBSTRING(name, 1, CHAR_LENGTH(name) - 4) = ?', [$file_name])
                                                ->whereRaw('SUBSTRING(name, -4) REGEXP "^ \\\\([0-9]+\\\\)$"');
                                    })
                                    ->orWhere('name', $file_name);
                                })
                                ->orderByDesc('created_at')
                                ->limit(1)
                                ->first();

        if (!$file) {
            return $file_name;
        }

        $next = (int) $file->next;

        return "{$file_name} ({$next})";
    }

    /**
     * Dispatching file event.
     *
     * @param  File  $file
     *
     * @return  void
     */
    private function dispatchFileEvent(File $file)
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
            $file_name = $this->formatFileName($folder_id, pathinfo($file_upload->getClientOriginalName(), PATHINFO_FILENAME));
            $file_extension = $file_upload->getClientOriginalExtension();
            $file_size = $file_upload->getSize();
            $file_mime_type = $file_upload->getMimeType();
            $folder_upload = 'uploads';

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

            $this->dispatchFileEvent($file);

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
