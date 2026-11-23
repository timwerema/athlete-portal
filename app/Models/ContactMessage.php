<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactMessage extends Model
{
    protected $fillable = ['share_link_id', 'sender_name', 'sender_school', 'sender_role', 'sender_email', 'body'];

    protected function casts(): array
    {
        return ['sender_email' => 'encrypted', 'body' => 'encrypted', 'read_at' => 'datetime'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }
}
