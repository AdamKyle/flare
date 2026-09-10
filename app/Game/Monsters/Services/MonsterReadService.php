<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\Monster;
use App\Game\Monsters\Transformers\MonsterDetailTransformer;

class MonsterReadService
{
    public function __construct(
        private readonly MonsterDetailTransformer $monsterDetailTransformer,
        private readonly MonsterGemEffectContextService $monsterGemEffectContextService,
    ) {}

    /**
     * Build the full factual detail representation for a single Monster.
     */
    public function detail(Monster $monster): array
    {
        $monster->loadMissing(['gameMap', 'questItem']);

        return $this->monsterDetailTransformer->transform($monster);
    }

    /**
     * Build the append-paginated Gem effect context list for a single Monster.
     */
    public function gemEffectContexts(Monster $monster, int $perPage, int $page): array
    {
        return $this->monsterGemEffectContextService->paginate($monster, $perPage, $page);
    }
}
