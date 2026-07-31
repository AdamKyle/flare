<?php

namespace App\Game\Tops\Services;

use App\Game\Tops\Events\CharacterTopsUpdated;
use App\Game\Tops\Events\DelveTopsUpdated;
use App\Game\Tops\Events\ExplorationTopsUpdated;
use App\Game\Tops\Events\FactionLoyaltyTopsUpdated;
use App\Game\Tops\Events\KingdomTopsUpdated;

class BroadcastTopsUpdateService
{
    public function __construct(
        private readonly CharacterTopsService $characterTopsService,
        private readonly ExplorationTopsService $explorationTopsService,
        private readonly DelveTopsService $delveTopsService,
        private readonly FactionLoyaltyTopsService $factionLoyaltyTopsService,
        private readonly KingdomTopsService $kingdomTopsService,
    ) {}

    public static function make(): self
    {
        $topsPeriodService = new TopsPeriodService();

        return new self(
            new CharacterTopsService($topsPeriodService),
            new ExplorationTopsService($topsPeriodService),
            new DelveTopsService($topsPeriodService),
            new FactionLoyaltyTopsService($topsPeriodService),
            new KingdomTopsService($topsPeriodService),
        );
    }

    public function broadcastCharacterCurrentMonth(): void
    {
        broadcast(new CharacterTopsUpdated($this->characterTopsService->leaderboard(['period' => 'current_month'])));
    }

    public function broadcastExplorationCurrentMonth(): void
    {
        broadcast(new ExplorationTopsUpdated($this->explorationTopsService->leaderboard(['period' => 'current_month'])));
    }

    public function broadcastDelveCurrentMonth(): void
    {
        broadcast(new DelveTopsUpdated($this->delveTopsService->leaderboard(['period' => 'current_month'])));
    }

    public function broadcastFactionLoyaltyCurrentMonth(): void
    {
        broadcast(new FactionLoyaltyTopsUpdated($this->factionLoyaltyTopsService->leaderboard(['period' => 'current_month'])));
    }

    public function broadcastKingdomCurrentMonth(): void
    {
        broadcast(new KingdomTopsUpdated($this->kingdomTopsService->leaderboard(['period' => 'current_month'])));
    }
}
