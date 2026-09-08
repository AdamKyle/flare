<?php

namespace App\Game\Quests\Values;

use App\Flare\Models\Quest;

enum QuestKind: string
{
    case CHAIN = 'chain';
    case ONE_OFF = 'one_off';
    case RAID = 'raid';

    /**
     * Resolve the factual kind of a Quest from its current persisted relationships.
     */
    public static function resolve(Quest $quest, bool $hasChildQuests): self
    {
        if (! is_null($quest->raid_id)) {
            return self::RAID;
        }

        if (! is_null($quest->parent_quest_id)) {
            return self::CHAIN;
        }

        if ($hasChildQuests) {
            return self::CHAIN;
        }

        return self::ONE_OFF;
    }

    /**
     * Return the Admin/Information tree bucket label for this Quest kind.
     */
    public function label(): string
    {
        return match ($this) {
            self::CHAIN => 'Quest Chain',
            self::ONE_OFF => 'One Off Quests',
            self::RAID => 'Raid Quests',
        };
    }
}
