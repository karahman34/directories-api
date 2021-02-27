<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Folder extends Model
{
    use HasFactory;

    protected $fillable = [
        'storage_id',
        'parent_folder_id',
        'name',
    ];

    /**
     * Get storage Model.
     *
     * @return  BelongsTo
     */
    public function storage()
    {
        return $this->belongsTo(Storage::class);
    }

    /**
     * Get files list.
     *
     * @return  HasMany
     */
    public function files()
    {
        return $this->hasMany(File::class);
    }
}
