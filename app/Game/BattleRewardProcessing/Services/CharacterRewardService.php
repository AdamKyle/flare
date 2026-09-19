<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Item as ItemModel;
use App\Flare\Models\Monster;
use App\Game\Core\Items\Builders\BuildCosmicItem;
use App\Game\Core\Items\Builders\BuildMythicItem;
use App\Game\Core\Items\Builders\BuildUniqueItem;
use App\Game\Core\Items\Values\RandomAffixTier;
use App\Game\Gems\Values\ResolvedAreaGemEffects;
use App\Game\Skills\Services\SkillService;
use Closure;
use Throwable;

class CharacterRewardService
{
    private ?Character $character = null;

    /**
     * @param CharacterXPService $characterXpService
     * @param CharacterCurrencyRewardService $characterCurrencyRewardService
     * @param SkillService $skillService
     * @param BuildUniqueItem $buildUniqueItem
     * @param BuildMythicItem $buildMythicItem
     * @param BuildCosmicItem $buildCosmicItem
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
     *
     * @param Character $character
     * @return CharacterRewardService
     */
    public function setCharacter(Character $character): CharacterRewardService
    {
        $this->character = $character;

        return $this;
    }

    /**
     * Register a heartbeat callback with the underlying Character XP service.
     *
     * @param ?Closure $callback
     * @return self
     */
    public function withHeartbeatCallback(?Closure $callback): self
    {
        $this->characterXpService->withHeartbeatCallback($callback);

        return $this;
    }

    /**
     * Distribute the XP to the character based on the monster.
     *
     * @param Monster $monster
     * @return CharacterRewardService
     */
    public function distributeCharacterXP(Monster $monster): CharacterRewardService
    {
        $this->characterXpService->setCharacter($this->character)->distributeCharacterXP($monster);

        return $this;
    }

    /**
     * Distribute a specific amount of XP
     *
     * @param int $xp
     * @return CharacterRewardService
     */
    public function distributeSpecifiedXp(int $xp): CharacterRewardService
    {

        $this->characterXpService->setCharacter($this->character)->distributeSpecifiedXp($xp);

        return $this;
    }

    /**
     * Distribute XP in a single checkpointed step, invoking the callback once it is applied.
     *
     * @param int $xp
     * @param ?Closure $checkpointCallback
     * @return CharacterRewardService
     */
    public function distributeCheckpointedXp(int $xp, ?Closure $checkpointCallback = null): CharacterRewardService
    {
        $this->characterXpService->setCharacter($this->character)->distributeCheckpointedXp($xp, $checkpointCallback);

        return $this;
    }

    /**
     * Distribute Skill Xp
     *
     * @param Monster $monster
     * @return CharacterRewardService
     */
    public function distributeSkillXP(Monster $monster): CharacterRewardService
    {
        $this->skillService->setSkillInTraining($this->character)->assignXPToTrainingSkill($this->character, $monster->xp);

        return $this;
    }

    /**
     * Give currencies.
     *
     * @param Monster $monster
     * @param int $totalKills
     * @return array
     */
    public function giveCurrencies(Monster $monster, $totalKills = 1): array
    {
        return $this->characterCurrencyRewardService->setCharacter($this->character)->giveCurrencies($monster, $totalKills);
    }

    /**
     * Plan the currency rewards for the Monster without applying them.
     *
     * @param Monster $monster
     * @param int $totalKills
     * @return array
     */
    public function planCurrencies(Monster $monster, int $totalKills = 1): array
    {
        return $this->characterCurrencyRewardService->setCharacter($this->character)->planCurrencies($monster, $totalKills);
    }

    /**
     * Apply a previously planned set of currency rewards to the Character.
     *
     * @param array $plan
     * @param ?ResolvedAreaGemEffects $resolvedAreaGemEffects
     * @return array
     */
    public function applyPlannedCurrencies(array $plan, ?ResolvedAreaGemEffects $resolvedAreaGemEffects = null): array
    {
        return $this->characterCurrencyRewardService->setCharacter($this->character)->applyPlannedCurrencies($plan, $resolvedAreaGemEffects);
    }

    /**
     * Return the invalid whole-currency calculation failure recorded during the most recent
     * currency operation, if one occurred.
     *
     * @return ?Throwable
     */
    public function currencyCalculationFailure(): ?Throwable
    {
        return $this->characterCurrencyRewardService->currencyCalculationFailure();
    }

    /**
     * Get the refreshed Character
     *
     * @return Character
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
     *
     * @param Monster $monster
     * @param ?ResolvedAreaGemEffects $resolvedAreaGemEffects
     * @return int
     */
    public function fetchXpForMonster(Monster $monster, ?ResolvedAreaGemEffects $resolvedAreaGemEffects = null): int
    {
        return $this->characterXpService->setCharacter($this->character)->fetchXpForMonster($monster, $resolvedAreaGemEffects);
    }

    /**
     * Return the invalid whole-XP calculation failure recorded during the most recent XP
     * operation, if one occurred.
     *
     * @return ?Throwable
     */
    public function xpCalculationFailure(): ?Throwable
    {
        return $this->characterXpService->xpCalculationFailure();
    }

    /**
     * Get a special gear drop for the paid affix tier amount.
     *
     * @param int $amountPaid
     * @return ItemModel
     */
    public function getSpecialGearDrop(int $amountPaid): ItemModel
    {
        return match ($amountPaid) {
            RandomAffixTier::LEGENDARY->value => $this->buildUniqueItem->fetchUniqueItem($this->character),
            RandomAffixTier::MYTHIC->value => $this->buildMythicItem->fetchMythicItem($this->character),
            RandomAffixTier::COSMIC->value => $this->buildCosmicItem->fetchCosmicItem($this->character),
        };
    }
}
