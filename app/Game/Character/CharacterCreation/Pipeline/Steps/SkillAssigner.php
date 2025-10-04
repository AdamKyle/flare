<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Flare\Values\BaseSkillValue;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Closure;
use DateTimeInterface;
use Illuminate\Support\Collection;

class SkillAssigner
{
    public function __construct(private readonly BaseSkillValue $baseSkillValue) {}

    /**
     * Assign all starting skills to the character using a single bulk insert.
     */
    public function process(CharacterBuildState $state, Closure $next): CharacterBuildState
    {
        $character = $state->getCharacter();

        if ($character === null) {
            return $next($state);
        }

        $now = $state->getNow() ?? now();

        $generalSkills = GameSkill::whereNull('game_class_id')->get();
        $classSkills = $character->class->gameSkills;

        $skills = $generalSkills->merge($classSkills)->unique('id')->values();

        $rows = $this->buildCharacterSkillRows($skills, $character, $now);

        if (! empty($rows)) {
            Skill::query()->insert($rows);
        }

        return $next($state);
    }

    /**
     * Build character skill rows for initial creation.
     */
    private function buildCharacterSkillRows(
        Collection $skills,
        Character $character,
        DateTimeInterface $timestamp
    ): array {
        return $skills->map(function (GameSkill $skill) use ($character, $timestamp) {
            $payload = $this->baseSkillValue->getBaseCharacterSkillValue($character, $skill);

            return array_merge($payload, [
                'character_id' => $character->id,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);
        })->values()->all();
    }
}
