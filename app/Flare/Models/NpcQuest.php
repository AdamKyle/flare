<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class NpcQuest extends Model {

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'npc_id',
        'quest_id',
    ];

    public function npc() {
        return $this->belongsTo(Npc::class, 'npc_id', 'id');
    }

    public function quest() {
        return $this->belongsTo(Quest::class, 'quest_id', 'id');
    }
}
