<?php

namespace App\Game\Skills\Listeners;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterSheet\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Skills\Calculators\SkillXPCalculator;
use App\Game\Skills\Events\SkillLeveledUpServerMessageEvent;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Values\SkillTypeValue;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;

class UpdateSkillListener
{
    /**
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param SkillXPCalculator $skillXPCalculator
     * @param BuildCharacterAttackTypes $buildCharacterAttackTypes
     * @param CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer
     * @param Manager $manager
     */
    public function __construct(
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly SkillXPCalculator $skillXPCalculator,
        private readonly BuildCharacterAttackTypes $buildCharacterAttackTypes,
        private readonly CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
        private readonly Manager $manager,
    ) {}

    /**
     * Award the event's Skill its XP, and half as much to Enchanting when the Skill is Disenchanting.
     *
     * @param UpdateSkillEvent $event
     * @return void
     */
    public function handle(UpdateSkillEvent $event): void
    {
        if ($this->normalizeMaxLevelSkill($event->skill)) {
            return;
        }

        $this->updateSkill($event->skill, $this->getSkillXp($event->skill, $event->monster));

        if (! $event->skill->type()->isDisenchanting()) {
            return;
        }

        $enchantingSkill = Skill::where('game_skill_id', GameSkill::where('type', SkillTypeValue::ENCHANTING->value)->first()->id)
            ->where('character_id', $event->skill->character_id)
            ->first();

        $xp = ceil($this->getSkillXp($enchantingSkill) / 2);

        if ($this->normalizeMaxLevelSkill($enchantingSkill)) {
            return;
        }

        $this->updateSkill($enchantingSkill, $xp);
    }

    /**
     * Return the Skill XP earned, raised by the Character's current map training bonus.
     *
     * @param Skill $skill
     * @param Monster|null $monster
     * @return float|int
     */
    private function getSkillXp(Skill $skill, ?Monster $monster = null): float|int
    {
        $gameMap = $skill->character->map->gameMap;

        $skillXP = $this->skillXPCalculator->fetchSkillXP($skill, $monster);

        if (is_null($gameMap->skill_training_bonus)) {
            return $skillXP;
        }

        return $skillXP + $skillXP * $gameMap->skill_training_bonus;
    }

    /**
     * Add the XP to the Skill, levelling it up for every full XP bar until it reaches its max level.
     *
     * @param Skill $skill
     * @param int $skillXP
     * @return void
     */
    private function updateSkill(Skill $skill, int $skillXP): void
    {
        if ($this->normalizeMaxLevelSkill($skill)) {
            return;
        }

        $newXp = $skill->xp + $skillXP;

        while ($newXp >= $skill->xp_max) {
            $newXp -= $skill->xp_max;

            $skill = $this->levelUpSkill($skill);

            if ($skill->level >= $skill->baseSkill->max_level) {
                $newXp = 0;
                break;
            }
        }

        $skill->update(['xp' => $newXp]);
    }

    /**
     * Raise the Skill one level, announce it, and refresh the Character's attack data when the Skill affects it.
     *
     * @param Skill $skill
     * @return Skill
     */
    private function levelUpSkill(Skill $skill): Skill
    {
        $level = min($skill->level + 1, $skill->baseSkill->max_level);

        $skill->update([
            'level' => $level,
            'xp_max' => $skill->can_train ? $level * 10 : $this->randomNumberGenerator->numberBetween(100, 350),
            'xp' => 0,
        ]);

        $skill = $skill->refresh();
        $character = $skill->character->refresh();

        event(new SkillLeveledUpServerMessageEvent($skill->character->user, $skill->refresh()));

        if ($this->shouldUpdateCharacterAttackData($skill->baseSkill)) {
            $this->updateCharacterAttackDataCache($character);
        }

        return $skill;
    }

    /**
     * Determine whether the Game Skill grants a per level modifier that changes the Character's attack data.
     *
     * @param GameSkill $skill
     * @return bool
     */
    private function shouldUpdateCharacterAttackData(GameSkill $skill): bool
    {
        return $skill->base_damage_mod_bonus_per_level > 0
            || $skill->base_healing_mod_bonus_per_level > 0
            || $skill->base_ac_mod_bonus_per_level > 0
            || $skill->fight_time_out_mod_bonus_per_level > 0
            || $skill->move_time_out_mod_bonus_per_level > 0;
    }

    /**
     * Rebuild the Character's attack data cache and broadcast the Character's refreshed base information.
     *
     * @param Character $character
     * @return void
     */
    private function updateCharacterAttackDataCache(Character $character): void
    {
        $this->buildCharacterAttackTypes->buildCache($character);

        $characterData = new ResourceItem($character->refresh(), $this->characterSheetBaseInfoTransformer);

        event(new UpdateBaseCharacterInformation($character->user, $this->manager->createData($characterData)->toArray()));
    }

    /**
     * Pin a Skill at or beyond its max level to its max level with no XP.
     *
     * @param Skill $skill
     * @return bool
     */
    private function normalizeMaxLevelSkill(Skill $skill): bool
    {
        if ($skill->level < $skill->baseSkill->max_level) {
            return false;
        }

        $skill->update([
            'level' => $skill->baseSkill->max_level,
            'xp' => 0,
        ]);

        return true;
    }
}
