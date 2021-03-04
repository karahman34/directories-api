<?php

namespace App\Helpers;

use App\Models\Folder;

class FolderHelper
{
    /**
     * Format folder name.
     *
     * @param   string  $folder_name
     * @param   string  $parent_folder_id
     *
     * @return  string
     */
    public static function formatFolderName(string $folder_name, string $parent_folder_id)
    {
        $raw_name = Folder::where('parent_folder_id', $parent_folder_id)
                                    ->where('name', $folder_name)
                                    ->exists();

        if (!$raw_name) {
            return $folder_name;
        } else {
            $last = Folder::where('parent_folder_id', $parent_folder_id)
                            ->where(function ($query) use ($folder_name) {
                                $query->whereRaw('SUBSTRING(name, 1, CHAR_LENGTH(name) - 4) = ?', [$folder_name])
                                        ->whereRaw('SUBSTRING(name, -4) REGEXP "^ \\\\([0-9]+\\\\)$"');
                            })
                            ->count();

            $next = $last + 1;

            return "{$folder_name} ({$next})";
        }
    }
}
