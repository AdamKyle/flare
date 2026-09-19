<?php

namespace App\Game\BattleRewardProcessing\Enums;

use RuntimeException;

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
    case GEM_WORLD_REWARDS = 'gem_world_rewards';
    case EXPLORATION_CONTEXT = 'exploration_context';
    case WINTER_EVENT = 'winter_event';
    case FACTION_LOYALTY_FAME = 'faction_loyalty_fame';
    case FACTION_LOYALTY_CURRENCIES = 'faction_loyalty_currencies';
    case FACTION_LOYALTY_UNIQUE_ITEM = 'faction_loyalty_unique_item';
    case FACTION_LOYALTY_XP = 'faction_loyalty_xp';
    case FINAL_PLAYER_UPDATES = 'final_player_updates';
    case MESSAGE_OUTBOX = 'message_outbox';

    /**
     * The full battle-ledger step order used by BATTLE, EXPLORATION, and AUTOMATION requests.
     *
     * @return array
     */
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
            self::GEM_WORLD_REWARDS,
            self::EXPLORATION_CONTEXT,
            self::WINTER_EVENT,
            self::FINAL_PLAYER_UPDATES,
            self::MESSAGE_OUTBOX,
        ];
    }

    /**
     * The seven-step ledger order used by FACTION_LOYALTY requests.
     *
     * @return array
     */
    public static function orderedForFactionLoyalty(): array
    {
        return [
            self::BUILD_REWARD_PLAN,
            self::FACTION_LOYALTY_FAME,
            self::FACTION_LOYALTY_CURRENCIES,
            self::FACTION_LOYALTY_UNIQUE_ITEM,
            self::FACTION_LOYALTY_XP,
            self::FINAL_PLAYER_UPDATES,
            self::MESSAGE_OUTBOX,
        ];
    }

    /**
     * The queue-wrapper-only step order for Quest, Raid Quest, and Guide Quest
     * requests. Their reward source handler runs outside this ledger; only the
     * final live-update/message-outbox queue wrapper steps are ledger-owned.
     *
     * @return array
     */
    public static function orderedForQuest(): array
    {
        return [
            self::FINAL_PLAYER_UPDATES,
            self::MESSAGE_OUTBOX,
        ];
    }

    /**
     * Resolve the persisted step ordering for the given request source type.
     *
     * @param BattleRewardRequestSourceType $sourceType
     * @return array
     */
    public static function orderedForSource(BattleRewardRequestSourceType $sourceType): array
    {
        return match ($sourceType) {
            BattleRewardRequestSourceType::FACTION_LOYALTY => self::orderedForFactionLoyalty(),
            BattleRewardRequestSourceType::QUEST,
            BattleRewardRequestSourceType::RAID_QUEST,
            BattleRewardRequestSourceType::GUIDE_QUEST => self::orderedForQuest(),
            BattleRewardRequestSourceType::BATTLE,
            BattleRewardRequestSourceType::EXPLORATION,
            BattleRewardRequestSourceType::AUTOMATION => self::ordered(),
            BattleRewardRequestSourceType::FUTURE => throw new RuntimeException(
                'No ledger step ordering exists for future reward requests.',
            ),
        };
    }
}
