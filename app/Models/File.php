<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class File extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'folder_id',
        'path',
        'name',
        'extension',
        'size',
        'mime_type',
        'folder_trashed',
        'is_public',
    ];

    protected $casts = [
        'size' => 'float',
    ];

    protected $keyType = 'string';

    public $incrementing = false;
    
    public static $folder = 'uploads';

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
     * Get the folder model.
     *
     * @return  BelongsTo
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    /**
     * Get owner id.
     *
     * @return  string
     */
    public function getOwnerId()
    {
        $this->load([
            'folder' => function ($query) {
                $query->withTrashed()->select('id', 'storage_id');
            },
            'folder.storage:id,user_id'
        ]);

        return $this->folder->storage->user_id;
    }

    /**
     * Check wheater the file is owned.
     *
     * @return  bool
     */
    public function isOwned()
    {
        if (!Auth::check()) {
            return false;
        }

        if (Auth::id() !== $this->getOwnerId()) {
            return false;
        }

        return true;
    }
}
