<?php

namespace App\Game\Battle\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\Monster;
use App\Game\Battle\Events\AttackTimeOutEvent;
use App\Game\Battle\Events\CharacterRevive;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\BattleRewardProcessing\Jobs\BattleAttackHandler;
use App\Game\BattleRewardProcessing\Services\BattleRewardService;
use App\Game\BattleRewardProcessing\Services\SecondaryRewardService;
use App\Game\BattleRewardProcessing\Services\WeeklyBattleService;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BattleEventHandler
{
    use FetchEquipped;

    private BattleRewardService $battleRewardService;

    private SecondaryRewardService $secondaryRewardService;

    private WeeklyBattleService $weeklyBattleService;

    public function __construct(BattleRewardService $battleRewardService, SecondaryRewardService $secondaryRewardService, WeeklyBattleService $weeklyBattleService)
    {
        $this->battleRewardService = $battleRewardService;
        $this->secondaryRewardService = $secondaryRewardService;
        $this->weeklyBattleService = $weeklyBattleService;
    }

    /**
     * Process the fact the character has died.
     */
    public function processDeadCharacter(Character $character, ?Monster $monster = null): void
    {
        $character->update(['is_dead' => true]);

        $character = $character->refresh();

        if (! is_null($monster)) {

            if (! is_null($monster->only_for_location_type)) {
                $this->weeklyBattleService->handleCharacterDeath($character, $monster);
            }
        }

        event(new AttackTimeOutEvent($character));

        event(new ServerMessageEvent($character->user, 'You are dead. Please revive yourself by clicking revive.'));
        event(new UpdateCharacterStatus($character));
    }

    /**
     * Processes what we should do when the monster dies.
     *
     * - Handles rewarding the player
     *
     * @param integer $characterId
     * @param integer $monsterId
     * @param boolean $includeXp
     * @return void
     */
    public function processMonsterDeath(int $characterId, int $monsterId, bool $includeXp = true): void
    {
        $this->battleRewardService->setUp($characterId, $monsterId)->handleBaseRewards($includeXp);
    }

    /**
     * Handle when a character revives.
     */
    public function processRevive(Character $character): Character
    {
        $character->update([
            'is_dead' => false,
        ]);

        $characterInCelestialFight = CharacterInCelestialFight::where('character_id', $character->id)->first();
        $characterHealth = $character->getInformation()->buildHealth();

        if (! is_null($characterInCelestialFight)) {
            $characterInCelestialFight->update([
                'character_current_health' => $characterHealth,
            ]);
        }

        $monsterFightCache = Cache::get('monster-fight-' . $character->id);

        if (! is_null($monsterFightCache)) {
            $monsterFightCache['health']['current_character_health'] = $characterHealth;

            Cache::put('monster-fight-' . $character->id, $monsterFightCache, 900);
        }

        event(new CharacterRevive($character->user, $characterHealth));

        event(new UpdateCharacterStatus($character));

        return $character->refresh();
    }
}
