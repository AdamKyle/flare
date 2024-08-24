<?php

namespace Tests\Traits;

use App\Flare\Models\UserSiteAccessStatistics;

trait CreateUserSiteAccessStatistics
{
    public function createUserSiteAccessStatistics(array $options = []): UserSiteAccessStatistics
    {
        return UserSiteAccessStatistics::factory()->create($options);
    }
}
