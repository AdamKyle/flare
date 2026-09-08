<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\Monster;
use App\Game\Monsters\Transformers\MonsterDetailTransformer;

class MonsterReadService
{
    public function __construct(
        private readonly MonsterDetailTransformer $monsterDetailTransformer,
    ) {}

    /**
     * Build the full factual detail representation for a single Monster.
     */
    public function detail(Monster $monster): array
    {
        $monster->loadMissing(['gameMap', 'questItem']);

        return $this->monsterDetailTransformer->transform($monster);
    }
}
