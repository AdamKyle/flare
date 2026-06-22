<?php

namespace App\Game\Core\Services;

use Illuminate\Support\Facades\Cache;

class CharacterRewardLockService
{
    public function run(int $characterId, callable $callback): mixed
    {
        return Cache::lock('character-rewards:' . $characterId, 300)->block(300, $callback);
    }
}
