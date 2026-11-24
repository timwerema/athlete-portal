<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $profile_id
 * @property int|null $share_link_id
 * @property string $sender_name
 * @property string $sender_school
 * @property string $sender_role
 * @property string $sender_email
 * @property string $body
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property-read Profile $profile
 */
class ContactMessage extends Model
{
    protected $fillable = ['share_link_id', 'sender_name', 'sender_school', 'sender_role', 'sender_email', 'body'];

    protected function casts(): array
    {
        return ['sender_email' => 'encrypted', 'body' => 'encrypted', 'read_at' => 'datetime'];
    }

    /** @return BelongsTo<Profile, $this> */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
