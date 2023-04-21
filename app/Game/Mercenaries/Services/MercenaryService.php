<?php

namespace App\Game\Mercenaries\Services;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterMercenary;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Mercenaries\Requests\PurchaseMercenaryRequest;
use App\Game\Mercenaries\Values\ExperienceBuffValue;
use App\Game\Mercenaries\Values\MercenaryValue;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;
use Illuminate\Database\Eloquent\Collection;

class MercenaryService {

    use ResponseBuilder;

    /**
     * Formats characters mercenaries.
     *
     * @param Collection $mercenaries
     * @return array
     */
    public function formatCharacterMercenaries(Collection $mercenaries): array {
        $mercData = [];

        foreach($mercenaries as $mercenary) {
            $mercData[] = [
                'id'                 => $mercenary->id,
                'name'               => $mercenary->type()->getName(),
                'level'              => $mercenary->current_level,
                'max_level'          => MercenaryValue::MAX_LEVEL,
                'at_max_level'       => $mercenary->type()->isAtMaxLevel($mercenary->current_level),
                'current_xp'         => $mercenary->current_xp,
                'xp_required'        => $mercenary->xp_required,
                'bonus'              => $mercenary->type()->getBonus($mercenary->current_level, $mercenary->reincarnated_bonus),
                'xp_increase'        => $mercenary->xp_increase,
                'xp_buff'            => $mercenary->xp_buff,
                'times_reincarnated' => $mercenary->times_reincarnated,
                'can_reincarnate'    => $mercenary->current_level === MercenaryValue::MAX_LEVEL && $mercenary->times_reincarnated !== MercenaryValue::MAX_REINCARNATION
            ];
        }

        return $mercData;
    }

    /**
     * Buy a mercenary.
     *
     * @param array $params
     * @param Character $character
     * @return array
     */
    public function purchaseMercenary(array $params, Character $character): array {
        if (!is_null($character->mercenaries->where('mercenary_type', $params['type'])->first())) {
            return $this->errorResult('No. You already have this Mercenary.');
        }

        if ($character->gold < MercenaryValue::MERCENARY_COST) {
            return $this->errorResult('You cannot afford to purchase this mercenary!');
        }

        try {
            $mercType = new MercenaryValue($params['type']);
        } catch (Exception $e) {
            return $this->errorResult('Invalid type.');
        }

        if ($params['type'] === MercenaryValue::CHILD_OF_COPPER_COINS) {
            $hasQuestItem = $character->inventory->slots->where('item.effect', ItemEffectsValue::GET_COPPER_COINS)->isNotEmpty();

            if (!$hasQuestItem) {
                return $this->errorResult('You need to complete the Quest: The Magic of Purgatory in Hell before being able to purchase this Mercenary.');
            }
        }

        $character->update([
            'gold' => $character->gold - $mercType->getCost()
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        CharacterMercenary::create([
            'character_id'         => $character->id,
            'mercenary_type'       => $params['type'],
            'current_level'        => 1,
            'current_xp'           => 0,
            'xp_required'          => $mercType->getNextLevelXP(),
            'reincarnated_bonus'   => 0,
        ]);

        $charactersMercenary = $character->refresh()->mercenaries;

        return $this->successResult([
            'merc_data'    => $this->formatCharacterMercenaries($charactersMercenary),
            'mercs_to_buy' => MercenaryValue::mercenaries($charactersMercenary),
            'message'      => 'You purchased: ' . $mercType->getName() . '!'
        ]);
    }

    /**
     * Reincarnate the mercenary.
     *
     * @param Character $character
     * @param CharacterMercenary $characterMercenary
     * @return array
     */
    public function reIncarnateMercenary(Character $character, CharacterMercenary $characterMercenary): array {
        if ($character->id !== $characterMercenary->character_id) {
            return $this->errorResult('Not allowed to do that.');
        }

        if (!$characterMercenary->type()->isAtMaxLevel($characterMercenary->current_level)) {
            return $this->errorResult('Mercenary is not at level 100.');
        }

        if ($characterMercenary->times_reincarnated >= MercenaryValue::MAX_REINCARNATION) {
            return $this->errorResult('Cannot reincarnate any more.');
        }

        if ($character->shards < MercenaryValue::REINCARNATION_COST) {
            return $this->errorResult('Not enough shards to reincarnate. Cost is 500 Shards.');
        }

        $character->update([
            'shards' => $character->shards - MercenaryValue::REINCARNATION_COST
        ]);

        $characterMercenary->update([
            'current_level'       => 1,
            'current_xp'          => 0,
            'xp_required'         => $characterMercenary->type()->getNextLevelXP(is_null($characterMercenary->xp_increase) ? 0.05 : $characterMercenary->xp_increase),
            'reincarnated_bonus'  => $characterMercenary->type()->getBonus($characterMercenary->current_level, $characterMercenary->reincarnated_bonus),
            'xp_increase'         => is_null($characterMercenary->xp_increase) ? 0.05 : $characterMercenary->xp_increase + 0.05,
            'times_reincarnated'  => is_null($characterMercenary->times_reincarnated) ? 1 : $characterMercenary->times_reincarnated + 1,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new ServerMessageEvent($character->user, 'Reincarnated ' . $characterMercenary->type()->getName() . ' back to level 1! Leveling back to level 100 will stack the bonuses! Get even more currencies!'));

        return $this->successResult([
            'merc_data'    => $this->formatCharacterMercenaries($character->mercenaries),
            'mercs_to_buy' => MercenaryValue::mercenaries($character->mercenaries),
            'message'      => 'Re-incarnated Mercenary!'
        ]);
    }

    /**
     * Purchase an XP buff for the mercenary.
     *
     * @param Character $character
     * @param CharacterMercenary $characterMercenary
     * @param string $type
     * @return array
     * @throws Exception
     */
    public function purchaseXpBuffForMercenary(Character $character, CharacterMercenary $characterMercenary, string $type): array {
        if ($character->id !== $characterMercenary->character_id) {
            return $this->errorResult('Not allowed to do that.');
        }

        $buffType = new ExperienceBuffValue($type);

        $cost = $buffType->getCost();

        if ($character->gold < $cost) {
            return $this->errorResult('You do not have the gold to do that.');
        }

        $character->update([
            'gold' => $character->gold - $cost
        ]);

        $characterMercenary->update([
            'xp_buff' => $buffType->getXPBuff()
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        event(new ServerMessageEvent($character->user, 'Reincarnated ' . $characterMercenary->type()->getName() . ' back to level 1! Leveling back to level 100 will stack the bonuses! Get even more currencies!'));

        return $this->successResult([
            'merc_data'    => $this->formatCharacterMercenaries($character->mercenaries),
            'mercs_to_buy' => MercenaryValue::mercenaries($character->mercenaries),
            'message'      => 'Applied the buff to the Mercenary'
        ]);
    }

    /**
     * Give xp to all mercenaries.
     *
     * @param Character $character
     * @return void
     */
    public function giveXpToMercenaries(Character $character): void {
        foreach ($character->mercenaries as $mercenary) {

            if ($mercenary->current_level === MercenaryValue::MAX_LEVEL) {
                continue;
            }

            $newXp = $mercenary->current_xp + MercenaryValue::XP_PER_KILL;
            $newXp = $newXp + $newXp * $mercenary->xp_buff;

            if ($newXp >= $mercenary->type()->getNextLevelXP($mercenary->xp_increase)) {
                $mercenary->update([
                    'current_level' => $mercenary->current_level + 1,
                    'current_xp'    => 0,
                    'xp_required'   => $mercenary->type()->getNextLevelXP($mercenary->xp_increase),
                ]);

                $mercenary = $mercenary->refresh();

                event(new ServerMessageEvent($character->user, 'Your Mercenary: ' .$mercenary->type()->getName(). ' has leveled up and is now: ' . $mercenary->current_level));
            } else {
                $mercenary->update([
                    'current_xp' => $newXp
                ]);
            }
        }
    }
}
