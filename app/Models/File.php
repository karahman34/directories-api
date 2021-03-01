<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'folder_id',
        'path',
        'name',
        'extension',
        'size',
    ];

    protected $casts = [
        'size' => 'float',
    ];

    /**
     * Get the folder model.
     *
     * @return  BelongsTo
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }
}
