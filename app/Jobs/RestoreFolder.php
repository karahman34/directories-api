<?php

namespace App\Jobs;

use App\Models\File;
use App\Models\Folder;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class RestoreFolder
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $folder;
    public $walked_files_ids = [];
    public $walked_folders_ids = [];
    
    /**
     * Create a new job instance.
     *
     * @param   Folder  $folder
     *
     * @return void
     */
    public function __construct(Folder $folder)
    {
        $this->folder = $folder;
    }

    /**
     * Get trashed sub files.
     *
     * @param   Folder  $parentFolder
     *
     * @return  Collection
     */
    private function getSubFiles(Folder $parentFolder)
    {
        return File::withTrashed()->select('id')->where('folder_id', $parentFolder->id)->get();
    }

    /**
     * Get trashed sub folders.
     *
     * @param   Folder  $parentFolder
     *
     * @return  Collection
     */
    private function getSubFolders(Folder $parentFolder)
    {
        return Folder::withTrashed()
                        ->select('id', 'parent_folder_id', 'deleted_at')
                        ->where('parent_folder_id', $parentFolder->id)
                        ->get();
    }

    /**
     * Get ids of the trashed files & folders.
     *
     * @param   Folder  $parentFolder
     *
     * @return  void
     */
    private function getSubFoldersAndFilesId(Folder $parentFolder)
    {
        if ($parentFolder->id !== $this->folder->id) {
            $this->walked_folders_ids[] = $parentFolder->id;
        }

        if (!$parentFolder->trashed() || $this->folder->id === $parentFolder->id) {
            $sub_files = $this->getSubFiles($parentFolder);
            if ($sub_files->count() > 0) {
                array_push($this->walked_files_ids, ...$sub_files->pluck('id')->toArray());
            }

            $sub_folders = $this->getSubFolders($parentFolder);
            if ($sub_folders->count() > 0) {
                $sub_folders->each(function (Folder $sub_folder) {
                    $this->getSubFoldersAndFilesId($sub_folder);
                });
            }
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->getSubFoldersAndFilesId($this->folder);

        $this->folder->restore();
        
        File::withTrashed()->whereIn('id', $this->walked_files_ids)->update([
            'folder_trashed' => 'N'
        ]);

        Folder::withTrashed()->whereIn('id', $this->walked_folders_ids)->update([
            'parent_folder_trashed' => 'N'
        ]);
    }
}
