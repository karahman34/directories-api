<?php

namespace App\Jobs;

use App\Models\Folder;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class IncreaseParentFolderSize
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $folder_id;
    public $size;

    public $walked_folder_ids = [];
    
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
     * Get Folder model.
     *
     * @return  Folder
     */
    private function getFolder()
    {
        return Folder::select('id', 'parent_folder_id')->where('id', $this->folder_id)->first();
    }

    /**
     * Increase folder size.
     *
     * @return  void
     */
    private function increaseFolderSize()
    {
        $folder = $this->getFolder();
        
        if ($folder) {
            $this->walked_folder_ids[] = $folder->id;

            if (!is_null($folder->parent_folder_id)) {
                $this->folder_id = $folder->parent_folder_id;
            
                $this->increaseFolderSize();
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
        $this->increaseFolderSize();

        // Increase folders size.
        Folder::whereIn('id', $this->walked_folder_ids)->increment('size', $this->size);
    }
}
