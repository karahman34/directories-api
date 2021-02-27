<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Storage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'space',
        'used_space',
    ];

    /**
     * Default space in kilobytes.
     *
     * @var int
     */
    public static $defaultSpace = 1048576;

    /**
     * Get the storage owner.
     *
     * @return  BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get folders list.
     *
     * @return  HasMany
     */
    public function folders()
    {
        return $this->hasMany(Folder::class);
    }
}
