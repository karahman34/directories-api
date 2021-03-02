<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Uuid;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'folder_id',
        'path',
        'name',
        'extension',
        'size',
        'mime_type',
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
     * Get the folder model.
     *
     * @return  BelongsTo
     */
    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }
}
