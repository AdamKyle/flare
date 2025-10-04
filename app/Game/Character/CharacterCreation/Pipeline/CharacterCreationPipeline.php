<?php

namespace App\Game\Character\CharacterCreation\Pipeline;

use App\Game\Character\CharacterCreation\Pipeline\Steps\BuildCache;
use App\Game\Character\CharacterCreation\Pipeline\Steps\CharacterCreator;
use App\Game\Character\CharacterCreation\Pipeline\Steps\ClassRankAssigner;
use App\Game\Character\CharacterCreation\Pipeline\Steps\FactionAssigner;
use App\Game\Character\CharacterCreation\Pipeline\Steps\MapPlacement;
use App\Game\Character\CharacterCreation\Pipeline\Steps\PassiveSkillAssigner;
use App\Game\Character\CharacterCreation\Pipeline\Steps\SkillAssigner;
use App\Game\Character\CharacterCreation\Pipeline\Steps\StarterWeaponAndInventory;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Illuminate\Pipeline\Pipeline;

class CharacterCreationPipeline
{
    public function __construct(private readonly Pipeline $pipeline) {}

    /**
     * Run the character creation pipeline and return the final state.
     */
    public function run(CharacterBuildState $state): CharacterBuildState
    {
        return $this->pipeline
            ->send($state)
            ->through($this->steps())
            ->via('process')
            ->thenReturn();
    }

    /**
     * @return array<int, string>
     */
    private function steps(): array
    {
        return [
            CharacterCreator::class,
            StarterWeaponAndInventory::class,
            MapPlacement::class,
            FactionAssigner::class,
            SkillAssigner::class,
            PassiveSkillAssigner::class,
            ClassRankAssigner::class,
            BuildCache::class,
        ];
    }
}
