<?php

namespace App\Flare\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\User;

class ServerMessageEvent
{
    use SerializesModels;

    /**
     * Type of server messsage.
     *
     * @var string
     */
    public $type;

    /**
     * User
     *
     * @var \App\User;
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param  \App\User $user
     * @return void
     */
    public function __construct(User $user, string $type)
    {
        $this->type = $type;
        $this->user = $user;
    }
}
