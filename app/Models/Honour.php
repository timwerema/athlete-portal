<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Honour extends Model
{
    protected $fillable = ['position', 'title', 'year'];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
