<?php

namespace App\Game\Adventures\View;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Core\Traits\CanHaveQuestItem;

class AdventureCompletedRewards {

    use CanHaveQuestItem;

    private static $baseReward = [
        'exp'   => 0,
        'gold'  => 0,
        'skill' => [
            'exp'         => 0,
            'skill_name'  => 'None in training.',
            'exp_towards' => '0.0',
        ],
        'items' => [],
    ];

    public static function CombineRewards(array $rewards, Character $character) {

        foreach ($rewards as $level => $levelRewards) {
            foreach ($levelRewards as $monster => $monsterRewards) {

                self::$baseReward['exp']   += $monsterRewards['exp'];
                self::$baseReward['gold']  += $monsterRewards['gold'];


                self::updateSkillRewards($monsterRewards);
                self::setItems($monsterRewards, $character);

            }
        }

        return self::$baseReward;
    }

    protected static function updateSkillRewards(array $monsterRewards) {
        if (!isset($monsterRewards['skill'])) {
            return;
        }

        self::$baseReward['skill']['exp']         += $monsterRewards['skill']['exp'];
        self::$baseReward['skill']['skill_name']   = $monsterRewards['skill']['skill_name'];
        self::$baseReward['skill']['exp_towards']  = $monsterRewards['skill']['exp_towards'];
    }

    protected static function setItems(array $monsterRewards, Character $character) {
        if (empty($monsterRewards['items'])) {
            return;
        }

        $items = [];

        foreach ($monsterRewards['items'] as $item) {
            $foundItem = Item::find($item['id']);

            if ($foundItem->type === 'quest') {
                // Do you have or have you completed a quest for this item?
                if (self::canRecieveItem($character, $foundItem->id)) {
                    // No duplicate items.
                    if (!self::hasItemInRewards($foundItem->id)) {
                        $item['can_have'] = true;
                    }
                } else {
                    $item['can_have'] = false;
                }
            } else {
                // Always just give regular items:
                $item['can_have'] = true;
            }

            // No duplicates
            if (!isset($item['can_have'])) {
                continue;
            }

            $item['item'] = $foundItem;

            $items[] = $item;
        }

        self::$baseReward['items'] = [...self::$baseReward['items'], ...$items];
    }

    private static function hasItemInRewards(int $itemId): bool {
        foreach (self::$baseReward['items']  as $item) {

            if ($item['id'] === $itemId) {
                return true;
            }
        }

        return false;
    }
}