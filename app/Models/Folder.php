<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class Folder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'storage_id',
        'parent_folder_id',
        'name',
        'size',
        'parent_folder_trashed',
    ];

    protected $casts = [
        'size' => 'float',
    ];

    protected $keyType = 'string';

    public $incrementing = false;

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::creating(function ($model) {
            $model->id = Uuid::uuid4()->toString();
        });
    }

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

    /**
     * Check if the model is root folder.
     *
     * @return  bool
     */
    public function isRoot()
    {
        return is_null($this->parent_folder_id);
    }
}
