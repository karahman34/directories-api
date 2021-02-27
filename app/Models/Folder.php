<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

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

    /**
     * Get sub folders.
     *
     * @return  HasMany
     */
    public function sub_folders()
    {
        return $this->hasMany(Folder::class, 'parent_folder_id');
    }

    /**
     * Scope a query to only include an owned folders.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOwned($query)
    {
        $storage = Auth::user()->storage()->select('id')->first();

        return $query->where('storage_id', $storage->id);
    }
}
