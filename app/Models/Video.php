<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Video extends Model
{
    protected $fillable = ['kind', 'title', 'url'];

    public const KINDS = ['highlight' => 'Highlights', 'full_match' => 'Full match'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
