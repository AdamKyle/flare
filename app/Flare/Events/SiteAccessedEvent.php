<?php

namespace App\Flare\Events;

use Illuminate\Queue\SerializesModels;

/**
 * @codeCoverageIgnore
 */
class SiteAccessedEvent
{
    use SerializesModels;

    public bool $signIn = false;

    public bool $register = false;

    public bool $loggedOut = false;

    public function __construct(bool $signIn = false, bool $register = false, bool $loggedOut = false)
    {
        $this->signIn = $signIn;
        $this->register = $register;
        $this->loggedOut = $loggedOut;
    }
}
