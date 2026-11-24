<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $position
 * @property string $title
 * @property int|null $year
 * @property-read Profile $profile
 */
class Honour extends Model
{
    protected $fillable = ['position', 'title', 'year'];

    /** @return BelongsTo<Profile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
