<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Builders\Character\Traits\FetchEquipped;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\Monster;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Events\CharacterRevive;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\BattleRewardProcessing\Services\SecondaryRewardService;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BattleEventHandler {

    use FetchEquipped;

    /**
     * @var BattleRewardService $battleRewardService
     */
    private BattleRewardService $battleRewardService;

    /**
     * @var SecondaryRewardService $secondaryRewardService
     */
    private SecondaryRewardService $secondaryRewardService;

    /**
     * @param BattleRewardService $battleRewardService
     */
    public function __construct(BattleRewardService $battleRewardService, SecondaryRewardService $secondaryRewardService) {
        $this->battleRewardService    = $battleRewardService;
        $this->secondaryRewardService = $secondaryRewardService;
    }

    /**
     * Process the fact the character has died.
     *
     * @param Character $character
     * @return void
     */
    public function processDeadCharacter(Character $character): void {
        $character->update(['is_dead' => true]);

        $character = $character->refresh();

        event(new AttackTimeOutEvent($character));

        event(new ServerMessageEvent($character->user, 'You are dead. Please revive yourself by clicking revive.'));
        event(new UpdateCharacterStatus($character));
    }

    /**
     * Process the fact the monster has died.
     *
     * @param int $characterId
     * @param int $monsterId
     * @return void
     * @throws Exception
     */
    public function processMonsterDeath(int $characterId, int $monsterId): void {
        $monster   = Monster::find($monsterId);
        $character = Character::find($characterId);

        if (is_null($monster)) {
            Log::error('Missing Monster for id: ' . $monsterId);

            return;
        }

        $this->battleRewardService->setUp($monster, $character)->handleBaseRewards();

        $this->secondaryRewardService->handleSecondaryRewards($character);
    }

    /**
     * Handle when a character revives.
     *
     * @param Character $character
     * @return Character
     */
    public function processRevive(Character $character): Character {
        $character->update([
            'is_dead' => false
        ]);

        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();
        $characterHealth           = $character->getInformation()->buildHealth();

        if (!is_null($characterInCelestialFight)) {
            $characterInCelestialFight->update([
                'character_current_health' => $characterHealth,
            ]);
        }

        $monsterFightCache = Cache::get('monster-fight-' . $character->id);

        if (!is_null($monsterFightCache)) {
            $monsterFightCache['health']['current_character_health'] = $characterHealth;

            Cache::put('monster-fight-' . $character->id, $monsterFightCache, 900);
        }

        event(new CharacterRevive($character->user, $characterHealth));

        event(new UpdateCharacterStatus($character));

        return $character->refresh();
    }
}
