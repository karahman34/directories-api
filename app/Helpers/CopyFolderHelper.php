<?php

namespace App\Helpers;

use App\Helpers\FileHelper;
use App\Models\File;
use App\Models\Folder;
use Illuminate\Support\Collection;

class CopyFolderHelper
{
    /**
     * Folder Model.
     *
     * @var Folder
     */
    private static $folder;

    /**
     * New Parent Folder Id.
     *
     * @var string
     */
    private static $new_parent_folder_id;

    /**
     * The newest parent folder.
     *
     * @var Folder|null
     */
    private static $new_parent_folder;

    /**
     * Set new copy folder.
     *
     * @param  Folder  $folder
     * @param  string  $new_parent_folder_id
     *
     * @return  self    $self
     */
    public static function set(Folder $folder, string $new_parent_folder_id)
    {
        self::$folder = $folder;
        self::$new_parent_folder_id = $new_parent_folder_id;

        return new self;
    }

    /**
     * Get sub folders.
     *
     * @param   Folder  $folder
     *
     * @return  Collection
     */
    private static function getSubFolders(Folder $folder)
    {
        return Folder::where('parent_folder_id', $folder->id)->get();
    }

    /**
     * Get sub files.
     *
     * @param   Folder  $folder
     *
     * @return  Collection
     */
    private static function getSubFiles(Folder $folder)
    {
        return File::where('folder_id', $folder->id)->get();
    }

    /**
     * Format folder name.
     *
     * @param   string  $name
     * @param   string  $parent_folder_id
     *
     * @return  string
     */
    private static function formatFolderName(string $name, string $parent_folder_id)
    {
        $raw_name_exist = Folder::where('parent_folder_id', $parent_folder_id)
                                    ->where('name', $name)
                                    ->exists();

        return $raw_name_exist ? "{$name} (Copy)" : $name;
    }

    /**
     * Copy folder.
     *
     * @param  Folder  $folder
     * @param  string  $parent_folder_id
     *
     * @return  void
     */
    private static function copyFolder(Folder $folder, string $parent_folder_id)
    {
        $folder_name = $folder->name;
        if ($folder->id === self::$folder->id) {
            $folder_name = self::formatFolderName($folder->name, $parent_folder_id);
        }

        $new_folder = Folder::create([
            'storage_id' => $folder->storage_id,
            'parent_folder_id' => $parent_folder_id,
            'name' => $folder_name,
            'size' => $folder->size,
        ]);

        if ($folder->id === self::$folder->id) {
            self::$new_parent_folder = $new_folder;
        }

        // Copy sub folders.
        $sub_folders = self::getSubFolders($folder);
        if ($sub_folders->count() > 0) {
            foreach ($sub_folders as $sub_folder) {
                self::copyFolder($sub_folder, $new_folder->id);
            }
        }

        // Copy sub files.
        $sub_files = self::getSubFiles($folder);
        if ($sub_files->count() > 0) {
            foreach ($sub_files as $sub_file) {
                FileHelper::copyFileModel($sub_file, $new_folder->id);
            }
        }
    }

    /**
     * Execute the job.
     *
     * @return Folder  $new_parent_folder
     */
    public static function copy()
    {
        self::copyFolder(self::$folder, self::$new_parent_folder_id);

        return self::$new_parent_folder;
    }
}
