<?php

namespace App\Jobs;

use App\Models\File;
use App\Models\Folder;
use App\Models\Storage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class DeleteFolder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Storage Model.
     *
     * @var Storage
     */
    public $storage;
    
    /**
     * Folder Model.
     *
     * @var Folder
     */
    public $folder;

    /**
     * Deleted folders id.
     *
     * @var array
     */
    public $deleted_folders_id = [];

    /**
     * Create a new job instance.
     *
     * @param   Storage   $storage
     * @param   Folder   $folder
     *
     * @return  void
     */
    public function __construct(Storage $storage, Folder $folder)
    {
        $this->storage = $storage;
        $this->folder = $folder;
    }

    /**
     * Dispatch Delete files job.
     *
     * @param   Collection  $files
     *
     * @return  void
     */
    private function deleteFiles(Collection $files)
    {
        DeleteFiles::dispatchSync($this->storage, $files);
    }
    
    /**
     * Get sub folders.
     *
     * @param   int|string  $folder_id
     *
     * @return  mixed
     */
    private function getSubFolder($folder_id)
    {
        return Folder::where('parent_folder_id', $folder_id)->get();
    }

    /**
     * Get sub files.
     *
     * @param   int|string  $folder_id
     *
     * @return  mixed
     */
    private function getSubFiles($folder_id)
    {
        return File::where('folder_id', $folder_id)->get();
    }

    /**
     * Delete folder.
     *
     * @param   int|string  $folder_id
     *
     * @return  void
     */
    private function deleteSubFoldersAndFiles($folder_id)
    {
        $sub_folders = $this->getSubFolder($folder_id);
        $sub_files = $this->getSubFiles($folder_id);

        if ($sub_folders->count() > 0) {
            foreach ($sub_folders as $sub_folder) {
                $this->deleteSubFoldersAndFiles($sub_folder->id);
            }
        }

        if ($sub_files->count() > 0) {
            $this->deleteFiles($sub_files);
        }

        $this->deleted_folders_id[] = $folder_id;
    }
    
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->deleteSubFoldersAndFiles($this->folder->id);

        Folder::whereIn('id', $this->deleted_folders_id)->delete();
    }
}
