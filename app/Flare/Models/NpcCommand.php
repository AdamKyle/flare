<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class NpcCommand extends Model {

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

    public function npc() {
        return $this->belongsTo(Npc::class);
    }
}
