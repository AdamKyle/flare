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
     *
     * Order matters: a Raid-flagged Quest is always a Raid Quest even when it also
     * participates in a chain; a Quest with a parent or actual child Quests is a Chain
     * Quest; everything else is a standalone one-off Quest. The persisted `is_parent`
     * flag is never used here: Flare 1.0 set it on every top-level Quest, including
     * top-level Quests with no actual children, so it cannot reliably distinguish a
     * Chain root from a One Off.
     *
     * @param  Quest  $quest  Quest to classify.
     * @param  bool  $hasChildQuests  Whether any other Quest currently has this Quest as its parent.
     * @return QuestKind Resolved Quest kind.
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
     *
     * @return string Human-facing Quest kind label.
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
