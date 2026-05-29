<?php

namespace App\Game\Core\Traits;

use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Character\CharacterSheet\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Services\CharacterService;
use App\Game\Messages\Types\CharacterMessageTypes;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

trait HandleCharacterLevelUp
{
    /**
     * Handle possible level up.
     */
    public function handlePossibleLevelUp(Character $character): Character
    {
        if (! $this->canCharacterGainXp($character)) {
            return $this->normalizeMaxLevelCharacter($character);
        }

        if ($character->xp >= $character->xp_next) {
            $leftOverXP = $character->xp - $character->xp_next;

            if ($leftOverXP > 0) {
                $this->handleLevelUps($character, $leftOverXP);
            }

            if ($leftOverXP <= 0) {
                $this->handleCharacterLevelUp($character, 0);
            }
        }

        return $character->refresh();
    }

    /**
     * Handle instances where we could have multiple level ups.
     */
    public function handleLevelUps(Character $character, int $leftOverXP): Character
    {
        if (! $this->canCharacterGainXp($character)) {
            return $this->normalizeMaxLevelCharacter($character);
        }

        $character = $this->handleCharacterLevelUp($character, $leftOverXP);

        if (! $this->canCharacterGainXp($character)) {
            return $this->normalizeMaxLevelCharacter($character);
        }

        if ($leftOverXP >= $character->xp_next) {
            $leftOverXP = $character->xp - $character->xp_next;

            if ($leftOverXP > 0) {
                $this->handleLevelUps($character, $leftOverXP);
            }

            if ($leftOverXP <= 0) {
                $this->handleLevelUps($character, 0);
            }
        }

        if ($leftOverXP < $character->xp_next) {
            $character->update([
                'xp' => $leftOverXP,
            ]);
        }

        return $character->refresh();
    }

    /**
     * Handle character level up.
     */
    protected function handleCharacterLevelUp(Character $character, int $leftOverXP): Character
    {
        if (! $this->canCharacterGainXp($character)) {
            return $this->normalizeMaxLevelCharacter($character);
        }

        resolve(CharacterService::class)->levelUpCharacter($character, $leftOverXP);

        $character = $character->refresh();

        if (! $this->canCharacterGainXp($character)) {
            $character = $this->normalizeMaxLevelCharacter($character);
        }

        CharacterAttackTypesCacheBuilder::dispatch($character);

        $this->updateCharacterStats($character);

        ServerMessageHandler::handleMessage($character->user, CharacterMessageTypes::LEVEL_UP, $character->level);

        return $character;
    }

    /**
     * Update the character stats.
     */
    protected function updateCharacterStats(Character $character): void
    {
        $characterData = new Item($character, resolve(CharacterSheetBaseInfoTransformer::class));
        $characterData = resolve(Manager::class)->createData($characterData)->toArray();

        event(new UpdateBaseCharacterInformation($character->user, $characterData));
    }

    /**
     * Can the character gain XP?
     */
    protected function canCharacterGainXp(Character $character): bool
    {
        return $character->level < $this->getCharacterMaxLevel($character);
    }

    /**
     * Normalize max-level character XP.
     */
    protected function normalizeMaxLevelCharacter(Character $character): Character
    {
        $maxLevel = $this->getCharacterMaxLevel($character);

        if ($character->level > $maxLevel) {
            $character->update([
                'level' => $maxLevel,
                'xp' => 0,
            ]);

            return $character->refresh();
        }

        if ($character->level === $maxLevel && $character->xp !== 0) {
            $character->update([
                'xp' => 0,
            ]);
        }

        return $character->refresh();
    }

    /**
     * Get the character max level.
     */
    protected function getCharacterMaxLevel(Character $character): int
    {
        if (! $this->hasContinueLevelingItem($character)) {
            return MaxLevel::MAX_LEVEL;
        }

        $configuration = MaxLevelConfiguration::query()->first();

        if (is_null($configuration)) {
            return MaxLevel::MAX_LEVEL;
        }

        return $configuration->max_level;
    }

    /**
     * Does the character have the continue-leveling item?
     */
    protected function hasContinueLevelingItem(Character $character): bool
    {
        if (is_null($character->inventory)) {
            return false;
        }

        return $character->inventory->slots->contains(function ($slot): bool {
            return ! is_null($slot->item) && $slot->item->effect === ItemEffectsValue::CONTINUE_LEVELING;
        });
    }
}
