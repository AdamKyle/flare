<?php

namespace Tests\Traits;

use App\Flare\Models\User;

trait CreateAutomationEventUser
{
    public function createAutomationEventUser(): User
    {
        $user = new User();
        $user->id = 123;

        return $user;
    }
}
