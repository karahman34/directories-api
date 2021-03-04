<?php

namespace App\Helpers;

use App\Jobs\DecreaseParentFolderSize;
use App\Jobs\IncreaseParentFolderSize;
use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileHelper
{
    /**
     * Format file name.
     *
     * @param   string  $folder_id
     * @param   string  $file_name
     *
     * @return  string
     */
    public static function formatFileName($folder_id, string $file_name)
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
     * Copy file.
     *
     * @param   File  $file
     *
     * @return  string  $new_path
     */
    public static function copy(File $file)
    {
        $folder_name = File::$folder;
        $new_name = Str::random(40) . '.' . $file->extension;
        $new_path = "{$folder_name}/{$new_name}";

        if (Storage::exists($new_path)) {
            return self::copy($file);
        } else {
            Storage::copy($file->path, $new_path);

            return $new_path;
        }
    }

    /**
     * Copy file model.
     *
     * @param   File    $file
     * @param   string  $folder_id the new folder target
     *
     * @return  File    $copiedFile
     */
    public static function copyFileModel(File $file, string $folder_id)
    {
        // Prep new file.
        $new_path = self::copy($file);
        $new_name = self::formatFileName($folder_id, $file->name);

        // Copy the file.
        $copiedFile = File::create([
            'folder_id' => $folder_id,
            'path' => $new_path,
            'name' => $new_name,
            'size' => $file->size,
            'extension' => $file->extension,
            'mime_type' => $file->mime_type,
        ]);

        return $copiedFile;
    }

    /**
     * Move file model.
     *
     * @param   File    $file
     * @param   string  $folder_id
     *
     * @return  File    $file
     */
    public static function moveFileModel(File $file, string $folder_id)
    {
        $old_folder_id = $file->folder_id;
        
        $file->update([
            'folder_id' => $folder_id,
        ]);
            
        DecreaseParentFolderSize::dispatchSync($old_folder_id, $file->size);
        IncreaseParentFolderSize::dispatchSync($folder_id, $file->size);

        return $file;
    }
}
