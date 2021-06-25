<?php

namespace App\Flare\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Http\Request;
use App\Flare\Models\GameRace;
use App\Flare\Models\GameClass;
use App\Flare\Models\User;

class ServerMessageEvent
{
    use SerializesModels;

    /**
     * Type of server messsage.
     *
     * @var string $type
     */
    public $type;

    /**
     * @var User $user
     */
    public $user;

    /**
     * @var string|null $link
     */
    public $link;

    /**
     * @var int|null $id
     */
    public $id;

    /**
     * @var mixed $forMessage
     */
    public $forMessage;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param string $type
     * @param mixed $forMessage | null
     * @param string|null $link
     * @param int|null $id
     */
    public function __construct(User $user, string $type, $forMessage = null, string $link = null, int $id = null)
    {
        $this->type        = $type;
        $this->user        = $user;
        $this->forMessage  = $forMessage;
        $this->link        = $link;
        $this->id          = $id;
    }
}
