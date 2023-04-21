<?php

namespace App\Flare\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\User;

class KingdomServerMessageEvent
{
    use SerializesModels;

    /**
     * @var string $type
     */
    public $type;

    /**
     * @var string $message
     */
    public $message;

    /**
     * @var User $user
     */
    public $user;

    /**
     * Create a new event instance.
     *
     * @param User $user
     * @param string $type
     * @param string $message
     */
    public function __construct(User $user, string $type, string $message = '') {
        $this->type    = $type;
        $this->message = $message;
        $this->user    = $user;
    }
}
