<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileView extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['share_link_id', 'viewed_on', 'visitor_hash'];
}
