<?php

namespace App\Flare\Services;

use App\Flare\Builders\BuildCosmicItem;
use App\Flare\Builders\BuildMythicItem;
use App\Flare\Builders\BuildUniqueItem;
use App\Flare\Models\Character;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Models\Monster;
use App\Flare\Values\RandomAffixDetails;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Skills\Services\SkillService;
use Closure;
use Exception;
use Exception;

class CharacterRewardService
{
    private ?Character $character = null;

    /**
     * Constructor
     */
    public function __construct(
        private readonly CharacterXPService $characterXpService,
        private readonly CharacterCurrencyRewardService $characterCurrencyRewardService,
        private readonly SkillService $skillService,
        private readonly BuildUniqueItem $buildUniqueItem,
        private readonly BuildMythicItem $buildMythicItem,
        private readonly BuildCosmicItem $buildCosmicItem,
    ) {}

    /**
     * Set the character.
     */
    public function setCharacter(Character $character): CharacterRewardService
    {
        $this->character = $character;

        return $this;
    }

    public function withHeartbeatCallback(?Closure $callback): self
    {
        $this->characterXpService->withHeartbeatCallback($callback);

        return $this;
    }

    /**
     * Distribute the XP to the character based on the monster.
     *
     * @throws Exception
     */
    public function distributeCharacterXP(Monster $monster): CharacterRewardService
    {
        $this->characterXpService->setCharacter($this->character)->distributeCharacterXP($monster);

        return $this;
    }

    /**
     * Distribute a specific amount of XP
     */
    public function distributeSpecifiedXp(int $xp): CharacterRewardService
    {

        $this->characterXpService->setCharacter($this->character)->distributeSpecifiedXp($xp);

        return $this;
    }

    public function distributeCheckpointedXp(int $xp, ?Closure $checkpointCallback = null): CharacterRewardService
    {
        $this->characterXpService->setCharacter($this->character)->distributeCheckpointedXp($xp, $checkpointCallback);

        return $this;
    }

    /**
     * Distribute Skill Xp
     *
     * @throws Exception
     */
    public function distributeSkillXP(Monster $monster): CharacterRewardService
    {
        $this->skillService->setSkillInTraining($this->character)->assignXPToTrainingSkill($this->character, $monster->xp);

        return $this;
    }

    /**
     * Give currencies.
     *
     * @throws Exception
     */
    public function giveCurrencies(Monster $monster, $totalKills = 1): array
    {
        return $this->characterCurrencyRewardService->setCharacter($this->character)->giveCurrencies($monster, $totalKills);
    }

    public function planCurrencies(Monster $monster, int $totalKills = 1): array
    {
        return $this->characterCurrencyRewardService->setCharacter($this->character)->planCurrencies($monster, $totalKills);
    }

    public function applyPlannedCurrencies(array $plan): array
    {
        return $this->characterCurrencyRewardService->setCharacter($this->character)->applyPlannedCurrencies($plan);
    }

    /**
     * Get the refreshed Character
     */
    public function getCharacter(): Character
    {
        return $this->character->refresh();
    }

    /**
     * Fetch the xp for the monster
     *
     * - Can return 0 if we cannot gain xp.
     * - Can return 0 if the xp we would gain is 0.
     * - Takes into account skills in training
     * - Takes into account Xp Bonuses such as items (Alchemy and quest)
     */
    public function fetchXpForMonster(Monster $monster): int
    {
        return $this->characterXpService->setCharacter($this->character)->fetchXpForMonster($monster);
    }

    /**
     * @throws Exception
     */
    public function getSpecialGearDrop(int $amountPaid): ItemModel
    {
        return match ($amountPaid) {
            RandomAffixDetails::LEGENDARY => $this->buildUniqueItem->fetchUniqueItem($this->character),
            RandomAffixDetails::MYTHIC => $this->buildMythicItem->fetchMythicItem($this->character),
            RandomAffixDetails::COSMIC => $this->buildCosmicItem->fetchCosmicItem($this->character),
        };
    }
}
