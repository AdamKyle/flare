<?php

namespace App\Game\Events\Values;

use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;

class GlobalEventForEventTypeValue
{
    public static function returnGlobalEventInfoForSeasonalEvents(int $eventType): array
    {

        $event = new EventType($eventType);

        if ($event->isWinterEvent()) {
            return [
                'max_kills' => 190000,
                'reward_every' => 10000,
                'next_reward_at' => 10000,
                'event_type' => EventType::WINTER_EVENT,
                'item_specialty_type_reward' => ItemSpecialtyType::CORRUPTED_ICE,
                'should_be_unique' => true,
                'unique_type' => RandomAffixDetails::LEGENDARY,
                'should_be_mythic' => false,
            ];
        }

        if ($event->isDelusionalMemoriesEvent()) {
            return [
                'max_kills' => 400000,
                'reward_every' => 20000,
                'next_reward_at' => 20000,
                'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
                'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
                'should_be_unique' => false,
                'unique_type' => RandomAffixDetails::MYTHIC,
                'should_be_mythic' => true,
            ];
        }

        return [];
    }

    public static function fetchDelusionalMemoriesGlobalEventSteps(): array
    {
        return [
            'battle',
            'craft',
            'enchant',
        ];
    }

    public static function returnCraftingEventGoal(): array
    {
        return [
            'max_crafts' => 500,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ];
    }

    public static function returnEnchantingEventGoal(): array
    {
        return [
            'max_enchants' => 500,
            'reward_every' => 100,
            'next_reward_at' => 100,
            'event_type' => EventType::DELUSIONAL_MEMORIES_EVENT,
            'item_specialty_type_reward' => ItemSpecialtyType::DELUSIONAL_SILVER,
            'should_be_unique' => true,
            'unique_type' => RandomAffixDetails::LEGENDARY,
            'should_be_mythic' => false,
        ];
    }
}
