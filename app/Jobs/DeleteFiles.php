<?php

namespace App\Jobs;

use App\Models\File;
use App\Models\Folder;
use App\Models\Storage as ModelsStorage;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class DeleteFiles
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The Storage model.
     *
     * @var App\Models\Storage
     */
    public $storage;

    /**
     * Containing The list File model.
     *
     * @var Collection
     */
    public $files;

    /**
     * Create a new job instance.
     *
     * @param  App\Models\Storage  $storage
     * @param  Collection  $files
     *
     * @return void
     */
    public function __construct(ModelsStorage $storage, Collection $files)
    {
        $this->storage = $storage;
        $this->files = $files;
    }

    /**
     * Decrease storage space.
     *
     * @param   float  $size
     *
     * @return  void
     */
    private function decreaseStorageSpace(float $size)
    {
        $this->storage->decrement('used_space', $size);
    }

    /**
     * Delete files model.
     *
     * @param   array  $deleted_files_id
     *
     * @return  void
     */
    private function deleteFilesModel(array $deleted_files_id)
    {
        File::whereIn('id', $deleted_files_id)->forceDelete();
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $size_decrease = 0;
        $deleted_files_id = [];

        $this->files->each(function (File $file) use (&$size_decrease, &$deleted_files_id) {
            if (Storage::exists($file->path) && Storage::delete($file->path)) {
                $size_decrease += $file->size;
                $deleted_files_id[] = $file->id;

                if ($file->folder_trashed === 'N' && !$file->trashed()) {
                    DecreaseParentFolderSize::dispatchSync($file->folder_id, $file->size);
                }
            }
        });

        $this->decreaseStorageSpace($size_decrease);
        $this->deleteFilesModel($deleted_files_id);
    }
}
