<?php

namespace App\Game\Events\Registry;

use App\Flare\Models\Event;
use App\Flare\Models\ScheduledEvent;
use App\Game\Events\Services\Concerns\EventEnder;
use App\Game\Events\Services\DelusionalMemoriesEventEnderService;
use App\Game\Events\Services\FeedbackEventEnderService;
use App\Game\Events\Services\RaidEventEnderService;
use App\Game\Events\Services\WeeklyCelestialEventEnderService;
use App\Game\Events\Services\WeeklyCurrencyEventEnderService;
use App\Game\Events\Services\WeeklyFactionLoyaltyEnderService;
use App\Game\Events\Services\WinterEventEnderService;
use App\Game\Events\Values\EventType;

class EventEnderRegistry
{
    /**
     * @var array<int, EventEnder>
     */
    private array $enders = [];

    /**
     * @param RaidEventEnderService $raid
     * @param WeeklyCurrencyEventEnderService $weeklyCurrency
     * @param WeeklyCelestialEventEnderService $weeklyCelestials
     * @param WeeklyFactionLoyaltyEnderService $weeklyFaction
     * @param WinterEventEnderService $winter
     * @param DelusionalMemoriesEventEnderService $delusional
     * @param FeedbackEventEnderService $feedback
     */
    public function __construct(
        RaidEventEnderService $raid,
        WeeklyCurrencyEventEnderService $weeklyCurrency,
        WeeklyCelestialEventEnderService $weeklyCelestials,
        WeeklyFactionLoyaltyEnderService $weeklyFaction,
        WinterEventEnderService $winter,
        DelusionalMemoriesEventEnderService $delusional,
        FeedbackEventEnderService $feedback
    ) {
        $this->enders = [
            $raid,
            $weeklyCurrency,
            $weeklyCelestials,
            $weeklyFaction,
            $winter,
            $delusional,
            $feedback,
        ];
    }

    /**
     * @param  EventType  $type
     * @param  ScheduledEvent  $scheduled
     * @param  Event  $current
     * @return void
     */
    public function end(EventType $type, ScheduledEvent $scheduled, Event $current): void
    {
        foreach ($this->enders as $ender) {
            if ($ender->supports($type) === false) {
                continue;
            }

            $ender->end($type, $scheduled, $current);

            return;
        }
    }
}
