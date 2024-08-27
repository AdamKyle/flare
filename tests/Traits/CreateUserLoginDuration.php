<?php

namespace Tests\Traits;

use App\Flare\Models\UserLoginDuration;

trait CreateUserLoginDuration
{
    public function createUserLoginDuration(array $options = []): UserLoginDuration
    {
        return UserLoginDuration::factory()->create($options);
    }
}
