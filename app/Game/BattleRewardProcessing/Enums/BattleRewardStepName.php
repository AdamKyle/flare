<?php

namespace App\Game\BattleRewardProcessing\Enums;

enum BattleRewardStepName: string
{
    case BUILD_REWARD_PLAN = 'build_reward_plan';
    case SKILL_POINTS = 'skill_points';
    case FACTION_POINTS = 'faction_points';
    case FACTION_LOYALTY_BOUNTY = 'faction_loyalty_bounty';
    case CURRENCY_REWARDS = 'currency_rewards';
    case SPECIFIC_LOCATION_REWARDS = 'specific_location_rewards';
    case ITEM_DROPS = 'item_drops';
    case WEEKLY_REWARDS = 'weekly_rewards';
    case SECONDARY_REWARDS = 'secondary_rewards';
    case GLOBAL_EVENT_PARTICIPATION = 'global_event_participation';
    case XP = 'xp';
    case EXPLORATION_CONTEXT = 'exploration_context';
    case WINTER_EVENT = 'winter_event';
    case FINAL_PLAYER_UPDATES = 'final_player_updates';
    case MESSAGE_OUTBOX = 'message_outbox';

    public static function ordered(): array
    {
        return [
            self::BUILD_REWARD_PLAN,
            self::SKILL_POINTS,
            self::FACTION_POINTS,
            self::FACTION_LOYALTY_BOUNTY,
            self::CURRENCY_REWARDS,
            self::SPECIFIC_LOCATION_REWARDS,
            self::ITEM_DROPS,
            self::WEEKLY_REWARDS,
            self::SECONDARY_REWARDS,
            self::GLOBAL_EVENT_PARTICIPATION,
            self::XP,
            self::EXPLORATION_CONTEXT,
            self::WINTER_EVENT,
            self::FINAL_PLAYER_UPDATES,
            self::MESSAGE_OUTBOX,
        ];
    }
}
