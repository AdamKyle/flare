<?php

namespace App\Game\Monsters\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Monster;
use App\Game\Core\Traits\ResponseBuilder;
use Psr\SimpleCache\InvalidArgumentException;

class MonsterStatsService
{
    use ResponseBuilder;

    public function __construct(private readonly MonsterListService $monsterListService) {}

    /**
     * Get a single monster's full stats from the same dataset the list view would show for the character.
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

        return $this->successResult($match);
    }
}
