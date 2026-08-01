<?php

namespace Tests\Traits;

use App\Flare\Models\DelveLog;

trait CreateDelveLog
{
    public function createDelveLog(array $options = []): DelveLog
    {
        return DelveLog::factory()->create($options);
    }
}
