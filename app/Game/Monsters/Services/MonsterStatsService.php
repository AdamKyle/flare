<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Monsters\Transformers\MonsterDetailTransformer;

class MonsterStatsService
{
    use ResponseBuilder;

    /**
     * @param MonsterListService $monsterListService
     * @param MonsterDetailTransformer $monsterDetailTransformer
     * @param MonsterGemEffectContextService $monsterGemEffectContextService
     */
    public function __construct(
        private readonly MonsterListService $monsterListService,
        private readonly MonsterDetailTransformer $monsterDetailTransformer,
        private readonly MonsterGemEffectContextService $monsterGemEffectContextService,
    ) {}

    /**
     * Return a Monster's detail representation using the effective cached payload and Gem effect context from the dataset the Character's Monster list shows.
     *
     * @param Character $character
     * @param Monster $monster
     * @return array
     */
    public function getMonsterStats(Character $character, Monster $monster): array
    {
        $dataset = $this->monsterListService->resolveMonsterDataSetForCharacter($character);

        $match = collect($dataset['data'] ?? [])->firstWhere('id', $monster->id);

        if (is_null($match)) {
            return $this->errorResult('We could not find the requested monster for the character’s current area.');
        }

        $currentContext = $this->monsterGemEffectContextService->forEffectiveMonster($monster, $match);

        return $this->successResult(
            $this->monsterDetailTransformer->transform(
                $monster,
                is_null($currentContext) ? [] : [$currentContext],
                $match,
            )
        );
    }
}
