<?php

namespace Tests\Traits;

use App\Flare\Models\Announcement;

trait CreateAnnouncement
{
    public function createAnnouncement(array $options = []): Announcement
    {
        return Announcement::factory()->create($options);
    }
}
