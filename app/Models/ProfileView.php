<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $share_link_id
 * @property string $viewed_on
 * @property string $visitor_hash
 */
class ProfileView extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['share_link_id', 'viewed_on', 'visitor_hash'];
}
