<?php

namespace App\Game\Core\Traits;

use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Models\Character;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Character\Builders\AttackBuilders\Jobs\CharacterAttackTypesCacheBuilder;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Services\CharacterService;
use App\Game\Messages\Types\CharacterMessageTypes;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

trait HandleCharacterLevelUp
{
    /**
     * Handle possible level up.
     */
    public function handlePossibleLevelUp(Character $character): Character
    {
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
        $this->handleCharacterLevelUp($character, $leftOverXP);

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
        resolve(CharacterService::class)->levelUpCharacter($character, $leftOverXP);

        $character = $character->refresh();

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
}
