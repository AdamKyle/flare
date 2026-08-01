<?php

namespace Tests\Traits;

use App\Flare\Models\QuestsCompleted;

trait CreateQuestsCompleted
{
    public function createQuestsCompleted(array $options = []): QuestsCompleted
    {
        return QuestsCompleted::factory()->create($options);
    }
}
