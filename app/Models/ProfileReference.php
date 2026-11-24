<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $position
 * @property string $name
 * @property string $role
 * @property string|null $organization
 * @property string $contact
 * @property Carbon $consent_confirmed_at
 * @property-read Profile $profile
 */
class ProfileReference extends Model
{
    protected $fillable = ['position', 'name', 'role', 'organization', 'contact', 'consent_confirmed_at'];

    protected function casts(): array
    {
        return ['contact' => 'encrypted', 'consent_confirmed_at' => 'datetime'];
    }

    /** @return BelongsTo<Profile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
