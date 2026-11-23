<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SeasonStat extends Model
{
    protected $fillable = ['season', 'team', 'competition', 'stats'];

    protected function casts(): array
    {
        return ['stats' => 'array'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
