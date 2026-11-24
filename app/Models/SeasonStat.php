<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $season
 * @property string $team
 * @property string|null $competition
 * @property array<string, int|float> $stats
 * @property-read Profile $profile
 */
class SeasonStat extends Model
{
    protected $fillable = ['season', 'team', 'competition', 'stats'];

    protected function casts(): array
    {
        return ['stats' => 'array'];
    }

    /** @return BelongsTo<Profile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
