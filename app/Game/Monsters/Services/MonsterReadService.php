<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\Monster;
use App\Game\Monsters\Transformers\MonsterDetailTransformer;

class MonsterReadService
{
    /**
     * @param  MonsterDetailTransformer  $monsterDetailTransformer  Factual Monster detail transformer.
     */
    public function __construct(
        private readonly MonsterDetailTransformer $monsterDetailTransformer,
    ) {}

    /**
     * Build the full factual detail representation for a single Monster.
     *
     * @param  Monster  $monster  Monster to load and transform.
     * @return array<string, mixed> Full factual Monster detail representation.
     */
    public function detail(Monster $monster): array
    {
        $monster->loadMissing(['gameMap', 'questItem']);

        return $this->monsterDetailTransformer->transform($monster);
    }
}
