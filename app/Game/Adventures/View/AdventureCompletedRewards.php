<?php

namespace App\Game\Adventures\View;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Game\Core\Traits\CanHaveQuestItem;

class AdventureCompletedRewards {

    use CanHaveQuestItem;

    private static $baseReward = [
        'exp'            => 0,
        'gold'           => 0,
        'faction_points' => 0,
        'skill'          => [
            'exp'         => 0,
            'skill_name'  => 'None in training.',
            'exp_towards' => '0.0',
        ],
        'items'           => [],
    ];

    public static function CombineRewards(array $rewards, Character $character) {
        foreach ($rewards as $level => $levelRewards) {
            foreach ($levelRewards as $monster => $monsterRewards) {

                self::$baseReward['exp']             += $monsterRewards['exp'];
                self::$baseReward['gold']            += $monsterRewards['gold'];
                self::$baseReward['faction_points']  += $monsterRewards['faction_points'];


                self::updateSkillRewards($monsterRewards);
                self::setItems($monsterRewards, $character);

            }
        }

        return self::$baseReward;
    }

    public static function messagesHasPlayerDeath(array $messages): bool {
        foreach ($messages as $message) {
            if (!isset($message['message'])) {
                return false;
            }

            if ($message['message'] === 'You have died during the fight! Death has come for you!') {
                return true;
            }
        }

        return false;
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
                if (self::canRecieveItem($character, $foundItem->id)) {
                    $item['can_have'] = true;
                } else {
                    $item['can_have'] = false;
                }
            } else {
                $item['can_have'] = true;
            }

            $item['item'] = $foundItem;
            $items[]      = $item;
        }

        if (!self::hasItemInRewards($foundItem->id)) {
            if (empty(self::$baseReward['items'])) {
                self::$baseReward['items'] = $items;
            } else {
                self::$baseReward['items'] = array_merge(self::$baseReward['items'], $items);
            }
        }
    }

    private static function hasItemInRewards(int $itemId): bool {
        foreach (self::$baseReward['items']  as $item) {

            /**
             * only in rare instances I have found by playing the game do we get here,
             * where there could be duplicates.
             */
            if ($item['id'] === $itemId) {
                // @codeCoverageIgnoreStart
                return true;
                // @codeCoverageIgnoreEnd
            }
        }

        return false;
    }
}