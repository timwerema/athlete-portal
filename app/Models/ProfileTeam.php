<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $position
 * @property string $kind
 * @property string $name
 * @property string|null $detail
 * @property int|null $start_year
 * @property int|null $end_year
 * @property-read Profile $profile
 */
class ProfileTeam extends Model
{
    protected $fillable = ['position', 'kind', 'name', 'detail', 'start_year', 'end_year'];

    public const KINDS = ['college' => 'College', 'club' => 'Club', 'high_school' => 'High school', 'national' => 'National team'];

    /** @return BelongsTo<Profile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
