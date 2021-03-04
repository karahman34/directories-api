<?php

namespace App\Jobs;

use App\Models\Folder;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DecreaseParentFolderSize
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $folder_id;
    public $size;

    public $recorded_folder_ids = [];
    
    /**
     * Create a new job instance.
     *
     * @param  string  $folder_id
     * @param  float  $size
     *
     * @return void
     */
    public function __construct(string $folder_id, float $size)
    {
        $this->folder_id = $folder_id;
        $this->size = $size;
    }

    /**
     * Get folder model.
     *
     * @return  Folder
     */
    private function getFolder()
    {
        return Folder::owned()->select('id', 'parent_folder_id', 'size')->where('id', $this->folder_id)->first();
    }

    /**
     * Decrease folder size.
     *
     * @return  void
     */
    private function decreaseParentSize()
    {
        $folder = $this->getFolder();

        $this->recorded_folder_ids[] = $folder->id;

        if (!is_null($folder->parent_folder_id)) {
            $this->folder_id = $folder->parent_folder_id;

            $this->decreaseParentSize();
        }
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->decreaseParentSize();

        // Decrease folders size.
        Folder::whereIn('id', $this->recorded_folder_ids)->decrement('size', $this->size);
    }
}
