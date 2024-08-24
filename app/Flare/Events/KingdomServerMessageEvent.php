<?php

namespace App\Flare\Events;

use App\Flare\Models\User;
use Illuminate\Queue\SerializesModels;

class KingdomServerMessageEvent
{
    use SerializesModels;

    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $message;

    /**
     * @var User
     */
    public $user;

    /**
     * Create a new event instance.
     */
    public function __construct(User $user, string $type, string $message = '')
    {
        $this->type = $type;
        $this->message = $message;
        $this->user = $user;
    }
}
