<?php

namespace Tests\Traits;

use App\Flare\Models\QuestItemSlot;

trait CreateQuestItemSlot{

    public function createInventorySlot(array $options = []): QuestItemSlot {
        return factory(QuestItemSlot::class)->create($options);
    }
}
