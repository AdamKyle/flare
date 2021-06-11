<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class NpcCommands extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'npc_id',
        'command',
        'command_type',
    ];

    protected $casts = [
        'command_type' => 'integer',
    ];
}
