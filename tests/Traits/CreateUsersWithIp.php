<?php

namespace Tests\Traits;

trait CreateUsersWithIp
{
    public function createUsersWithIp(int $count, string $ip): void
    {
        for ($userIndex = 0; $userIndex < $count; $userIndex++) {
            $this->createUser(['ip_address' => $ip]);
        }
    }
}
