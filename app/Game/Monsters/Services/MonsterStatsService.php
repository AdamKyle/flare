<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Monsters\Transformers\MonsterDetailTransformer;
use Psr\SimpleCache\InvalidArgumentException;

class MonsterStatsService
{
    use ResponseBuilder;

    public function __construct(
        private readonly MonsterListService $monsterListService,
        private readonly MonsterDetailTransformer $monsterDetailTransformer,
        private readonly MonsterGemEffectContextService $monsterGemEffectContextService,
    ) {}

    /**
     * Get a single monster's full shared detail representation, scoped to the
     * Character's current Gem effect context, from the same dataset the list
     * view would show for the character.
     *
     * @throws InvalidArgumentException
     */
    public function getMonsterStats(Character $character, Monster $monster): array
    {
        $dataset = $this->monsterListService->resolveMonsterDataSetForCharacter($character);

        $match = collect($dataset['data'] ?? [])->firstWhere('id', $monster->id);

        if (is_null($match)) {
            return $this->errorResult('We could not find the requested monster for the character’s current area.',
            );
        }

        $currentContext = $this->monsterGemEffectContextService->forEffectiveMonster($monster, $match);

        return $this->successResult(
            $this->monsterDetailTransformer->transform(
                $monster,
                is_null($currentContext) ? [] : [$currentContext],
            )
        );
    }
}
