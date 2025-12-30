<?php

namespace App\Game\BattleRewardProcessing\Services;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\GameMap;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Values\MapNameValue;
use App\Game\BattleRewardProcessing\Handlers\BattleGlobalEventParticipationHandler;
use App\Game\BattleRewardProcessing\Handlers\BattleMessageHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionLoyaltyBountyHandler;
use App\Game\BattleRewardProcessing\Jobs\Events\WinterEventChristmasGiftHandler;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\GoldRush;
use App\Game\Events\Values\EventType;
use App\Game\Factions\FactionLoyalty\Events\FactionLoyaltyUpdate;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Skills\Services\SkillService;
use Exception;
use Illuminate\Support\Facades\Cache;
use Throwable;

class BattleRewardService
{

    /**
     * @var ?Character $characterId
     */
    private ?Character $character;

    /**
     * @var ?Monster $monsterId
     */
    private ?Monster $monster;

    /**
     * @var array $context
     */
    private array $context;

    /**
     * @param BattleMessageHandler $battleMessageHandler
     * @param CharacterRewardService $characterRewardService
     * @param FactionHandler $factionHandler
     * @param FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler
     * @param FactionLoyaltyService $factionLoyaltyService
     * @param GoldRush $goldRush
     * @param BattleLocationRewardService $battleLocationRewardService
     * @param DropCheckService $dropCheckService
     * @param WeeklyBattleService $weeklyBattleService
     * @param SecondaryRewardService $secondaryRewardService
     * @param BattleGlobalEventParticipationHandler $battleGlobalEventParticipationHandler
     * @param SkillService $skillService
     */
    public function __construct(
        private readonly BattleMessageHandler $battleMessageHandler,
        private readonly CharacterRewardService $characterRewardService,
        private readonly FactionHandler $factionHandler,
        private readonly FactionLoyaltyBountyHandler $factionLoyaltyBountyHandler,
        private readonly FactionLoyaltyService $factionLoyaltyService,
        private readonly GoldRush $goldRush,
        private readonly BattleLocationRewardService $battleLocationRewardService,
        private readonly DropCheckService $dropCheckService,
        private readonly WeeklyBattleService $weeklyBattleService,
        private readonly SecondaryRewardService $secondaryRewardService,
        private readonly BattleGlobalEventParticipationHandler $battleGlobalEventParticipationHandler,
        private readonly SkillService $skillService,
    ) {}

    /**
     * Set up the battle reward service
     *
     * @param integer $characterId
     * @param integer $monsterId
     * @return BattleRewardService
     */
    public function setUp(int $characterId, int $monsterId): BattleRewardService
    {

        $this->character = Character::find($characterId);
        $this->monster = Monster::find($monsterId);

        return $this;
    }

    /**
     * Set the context for the service.
     *
     * @param array $context
     * @return $this
     */
    public function setContext(array $context): BattleRewardService {
        $this->context = $context;

        return $this;
    }

    /**
     * @param bool $includeWinterEvent
     * @return void
     * @throws Throwable
     */
    public function processRewards(bool $includeWinterEvent = false): void {

        if (is_null($this->character) || is_null($this->monster)) {
            return;
        }

        $this->handleAwardingXP();
        $this->handleAwardSkillPoints();
        $this->handleFactionPoints();
        $this->handleFactionLoyaltyBounty();
        $this->handleCurrencyRewards();
        $this->handleSpecificLocationRewards();
        $this->handleItemDrops();
        $this->handleWeeklyFightRewards();
        $this->handleSecondaryRewards();
        $this->handleGlobalEventParticipation();

        if ($includeWinterEvent) {
            WinterEventChristmasGiftHandler::dispatch($this->character->id)->onConnection('event_battle_reward')->onQueue('event_battle_reward')->delay(now()->addSeconds(2));
        }
    }

    /**
     * Handle awarding XP and Skill XP
     *
     * @return void
     * @throws Exception
     */
    private function handleAwardingXP(): void {
        if (!isset($this->context['total_xp']) && !isset($this->context['total_creatures'])) {
            $this->characterRewardService->setCharacter($this->character)
                ->distributeCharacterXP($this->monster);

            $this->character = $this->character->refresh();

            return;
        }

        $totalXP = $this->context['total_xp'];
        $totalCreatures = $this->context['total_creatures'];

        $user = $this->character->user;

        $this->battleMessageHandler->handleMessageForExplorationXp($user, $totalCreatures, $totalXP);

        $this->characterRewardService->setCharacter($this->character)->distributeSpecifiedXp($totalXP);

        $this->character = $this->character->refresh();
    }

    /**
     * Handle awarding skill experience.
     *
     * @return void
     * @throws Exception
     */
    private function handleAwardSkillPoints(): void {

        if (!isset($this->context['total_skill_xp'])) {
            $this->characterRewardService->setCharacter($this->character)
                ->distributeSkillXP($this->monster);

            $this->character = $this->character->refresh();

            return;
        }

        $totalSkillPoints = $this->context['total_skill_xp'];

        $this->skillService->setSkillInTraining($this->character)->giveXpToTrainingSkill($this->character, $totalSkillPoints);

        $this->character = $this->character->refresh();
    }

    /**
     * Handle awarding faction points.
     *
     * @return void
     * @throws Throwable
     */
    private function handleFactionPoints(): void {

        $gameMap = $this->character->map->gameMap;

        if ($gameMap->mapType()->isPurgatory()) {
            return;
        }

        if ($this->character->is_auto_battling) {
            return;
        }

        if (!isset($this->context['total_faction_points'])) {
            $this->factionHandler->handleFaction($this->character, $this->monster);

            $this->character = $this->character->refresh();

            return;
        }

        $totalPoints = $this->context['total_faction_points'];

        $this->factionHandler->awardFactionPointsFromBatch($this->character, $totalPoints);

        $this->character = $this->character->refresh();
    }

    /**
     * Handles Faction Bounties.
     *
     * @return void
     */
    private function handleFactionLoyaltyBounty(): void {
        $gameMap = $this->character->map->gameMap;

        if ($gameMap->mapType()->isPurgatory()) {
            return;
        }

        if (!isset($this->context['total_creatures'])) {
            $this->factionLoyaltyBountyHandler->handleBounty($this->character, $this->monster);

            $this->character = $this->character->refresh();

            return;
        }

        $totalCreatures = $this->context['total_creatures'];

        $this->factionLoyaltyBountyHandler->handleBounty($this->character, $this->monster, $totalCreatures);

        $this->character = $this->character->refresh();

        event(new FactionLoyaltyUpdate($this->character->user, $this->factionLoyaltyService->getLoyaltyInfoForPlane($this->character)));
    }

    /**
     * Handle currency rewards
     *
     * @return void
     * @throws Exception
     */
    private function handleCurrencyRewards(): void {
        $totalKills = 1;

        if (isset($this->context['total_creatures'])) {
            $totalKills = $this->context['total_creatures'];
        }

        $this->characterRewardService->setCharacter($this->character)->giveCurrencies($this->monster, $totalKills);

        $character = $this->character->refresh();

        $this->goldRush->processPotentialGoldRush($character);

        $this->character = $character->refresh();
    }

    /**
     * Handle specific location rewards
     *
     * @return void
     */
    private function handleSpecificLocationRewards(): void {
        $totalKills = 1;

        if (isset($this->context['total_creatures'])) {
            $totalKills = $this->context['total_creatures'];
        }

        $this->battleLocationRewardService->setContext($this->character, $this->monster)->handleLocationSpecificRewards($totalKills);
    }

    /**
     * Process enemy drops.
     *
     * @return void
     * @throws Exception
     */
    private function handleItemDrops(): void {
        $totalKills = 1;

        if (isset($this->context['total_creatures'])) {
            $totalKills = $this->context['total_creatures'];
        }

        if ($totalKills > 1) {
            for ($i = 0; $i < $totalKills; $i++) {
                $this->dropCheckService->process($this->character, $this->monster);

                $this->character = $this->character->refresh();
            }

            return;
        }

        $this->dropCheckService->process($this->character, $this->monster);
    }

    /**
     * Handle weekly fight rewards, only when not exploring.
     *
     * @return void
     * @throws Exception
     */
    private function handleWeeklyFightRewards(): void {
        if ($this->character->is_auto_battling) {
            return;
        }

        $this->character = $this->weeklyBattleService->handleMonsterDeath($this->character, $this->monster);
    }

    /**
     * Handle secondary rewards.
     *
     * - Class Ranks
     * - Item Skills
     *
     * @return void
     * @throws Exception
     */
    private function handleSecondaryRewards(): void {
        $totalKills = 1;

        if (isset($this->context['total_creatures'])) {
            $totalKills = $this->context['total_creatures'];
        }

        $this->secondaryRewardService->handleSecondaryRewards($this->character, $totalKills);

        $this->character = $this->character->refresh();
    }

    /**
     * Handle event participation.
     *
     * @return void
     * @throws Exception
     */
    private function handleGlobalEventParticipation(): void {
        $cacheTtl = now()->addSeconds(15);

        $event = Cache::remember(
            'battle_reward_service:active_event',
            $cacheTtl,
            static function (): ?Event {
                return Event::whereIn('type', [
                    EventType::WINTER_EVENT,
                    EventType::DELUSIONAL_MEMORIES_EVENT,
                ])->first();
            }
        );

        if (is_null($event)) {
            return;
        }

        $globalEventGoal = Cache::remember(
            'battle_reward_service:global_event_goal:' . $event->type,
            $cacheTtl,
            static function () use ($event): ?GlobalEventGoal {
                return GlobalEventGoal::where('event_type', $event->type)->first();
            }
        );

        $gameMapArrays = Cache::remember(
            'battle_reward_service:global_event_map_ids',
            $cacheTtl,
            static function (): array {
                return GameMap::whereIn('name', [
                    MapNameValue::ICE_PLANE,
                    MapNameValue::DELUSIONAL_MEMORIES,
                ])->pluck('id')->toArray();
            }
        );

        if (is_null($globalEventGoal) || ! in_array($this->character->map->game_map_id, $gameMapArrays)) {
            return;
        }

        $totalKills = 1;

        if (isset($this->context['total_creatures'])) {
            $totalKills = $this->context['total_creatures'];
        }

        $this->battleGlobalEventParticipationHandler->handleGlobalEventParticipation($this->character, $globalEventGoal, $totalKills);

        $this->character = $this->character->refresh();
    }
}
