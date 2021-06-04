<?php

namespace App\Flare\Events;

use Illuminate\Queue\SerializesModels;

class SiteAccessedEvent {
    use SerializesModels;

    /**
     * @var bool $signIn
     */
    public bool $signIn = false;

    /**
     * @var bool $register
     */
    public bool $register = false;

    /**
     * @var bool $loggedOut
     */
    public bool $loggedOut = false;

    public function __construct(bool $signIn = false, bool $register = false, bool $loggedOut = false) {
        $this->signIn    = $signIn;
        $this->register  = $register;
        $this->loggedOut = $loggedOut;
    }
}
