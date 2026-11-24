<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $position
 * @property string $kind
 * @property string $title
 * @property string $url
 * @property-read Profile $profile
 */
class Video extends Model
{
    protected $fillable = ['position', 'kind', 'title', 'url'];

    public const KINDS = ['highlight' => 'Highlights', 'full_match' => 'Full match'];

    /** @return BelongsTo<Profile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
