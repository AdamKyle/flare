<?php

namespace App\Game\Skills\Services;

use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GemBagSlot;
use App\Flare\Models\Skill;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Gems\Values\GemTierValue;
use App\Game\Skills\Builders\GemBuilder;
use App\Game\Skills\Values\SkillTypeValue;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class GemService {

    use ResponseBuilder;

    /**
     * @var GemBuilder $gameBuilder
     */
    private GemBuilder $gemBuilder;

    /**
     * @param GemBuilder $gemBuilder
     */
    public function __construct(GemBuilder $gemBuilder) {
        $this->gemBuilder = $gemBuilder;
    }

    /**
     * Generate the gem.
     *
     * @param Character $character
     * @param int $tier
     * @return array
     * @throws Exception
     */
    public function generateGem(Character $character, int $tier): array {

        if (!$this->canAffordCost($character, $tier)) {
            return $this->errorResult('You do not have the required currencies to craft this item.');
        }

        if ($character->isInventoryFull()) {
            return $this->errorResult('You do not have enough space in your inventory.');
        }

        $character = $this->payForGem($character, $tier);

        event(new CraftedItemTimeOutEvent($character));

        $characterSkill = $this->getCraftingSkill($character);

        if ($this->skillLevelToHigh($characterSkill, $tier)) {
            ServerMessageHandler::sendBasicMessage($character->user, 'This gem tier is too easy, you get no XP for this craft');
        }

        if (!$this->canCraft($characterSkill, (new GemTierValue($tier))->maxForTier()['chance'])) {

            ServerMessageHandler::sendBasicMessage($character->user, 'You failed to craft the gem, the item explodes before you into a pile of wasted effort and time.');

            return $this->successResult();
        }

        $gemBagEntry = $this->giveGem($character, $tier);

        $character = $this->updateCharacterCurrencies($character, $tier);

        if (!$this->skillLevelToHigh($characterSkill, $tier)) {
            event(new UpdateSkillEvent($characterSkill));
        }

        ServerMessageHandler::handleMessage($character->user, 'crafted_gem', $gemBagEntry->gem->name, $gemBagEntry->id);

        return $this->successResult();
    }

    /**
     * Get tiers that are craftable.
     *
     * @param Character $character
     * @return array
     * @throws Exception
     */
    public function getCraftableTiers(Character $character): array {
        $craftableSkill = $this->getCraftingSkill($character);
        $craftableTiers = [];

        foreach (GemTierValue::$values as $tier) {
            $tierValue = (new GemTierValue($tier))->maxForTier();

            if ($craftableSkill->level >= $tierValue['min_level']) {
                $craftableTiers[] = $tierValue;
            }
        }

        return $craftableTiers;
    }

    /**
     * Skill level is too low.
     *
     * @param Skill $skill
     * @param int $tier
     * @return bool
     * @throws Exception
     */
    protected function skillLevelToLow(Skill $skill, int $tier): bool {
        $data = (new GemTierValue($tier))->maxForTier();

        if ($skill->level < $data['min_level']) {
            ServerMessageHandler::sendBasicMessage($skill->character->user, 'This gem tier is way to difficult for you. The minimum level is: ' . $data['min_level']);

            return true;
        }

        return false;
    }

    /**
     * Skill level too high.
     *
     * @param Skill $skill
     * @param int $tier
     * @return bool
     * @throws Exception
     */
    protected function skillLevelToHigh(Skill $skill, int $tier): bool {
        $data = (new GemTierValue($tier))->maxForTier();

        if ($skill->level > $data['max_level']) {
            ServerMessageHandler::sendBasicMessage($skill->character->user, 'This gem tier is too easy to craft, you will get no XP for crafting gems of the tier: ' . $data['min_level']);

            return true;
        }

        return false;
    }

    /**
     * Give the gem.
     *
     * @param Character $character
     * @param int $tier
     * @return GemBagSlot
     * @throws Exception
     */
    protected function giveGem(Character $character, int $tier): GemBagSlot {
        $gem = $this->gemBuilder->buildGem($tier);

        $foundGem = $character->gemBag->gemSlots()->where('gem_id', $gem->id)->first();

        if (!is_null($foundGem)) {
            $foundGem->update(['amount' => $foundGem->amount + 1]);

            return $character->gemBag->refresh();
        }

        return $character->gemBag->gemSlots()->create([
            'character_id' => $character->id,
            'gem_id'       => $gem->id,
            'amount'       => 1,
        ]);
    }

    /**
     * Can player afford the gem?
     *
     * @param Character $character
     * @param int $tier
     * @return bool
     * @throws Exception
     */
    protected function canAffordCost(Character $character, int $tier): bool {
        $data = (new GemTierValue($tier))->maxForTier();

        $goldDust    = $character->gold_dust;
        $shards      = $character->shards;
        $copperCoins = $character->copper_coins;

        return $goldDust >= $data['cost']['gold_dust'] &&
               $shards >= $data['cost']['shards'] &&
               $copperCoins >= $data['cost']['copper_coins'];
    }

    /**
     * For the cost of the gem based on tier.
     *
     * @param Character $character
     * @param int $tier
     * @return Character
     * @throws Exception
     */
    protected function payForGem(Character $character, int $tier): Character {
        $data = (new GemTierValue($tier))->maxForTier();

        $goldDust    = $character->gold_dust;
        $shards      = $character->shards;
        $copperCoins = $character->copper_coins;

        $newGoldDust    = $goldDust - $data['cost']['gold_dust'];
        $newShards      = $shards - $data['cost']['shards'];
        $newCopperCoins = $copperCoins - $data['cost']['copper_coins'];

        $character->update([
            'gold_dust'    => $newGoldDust,
            'shards'       => $newShards,
            'copper_coins' => $newCopperCoins,
        ]);

        return $character->refresh();
    }

    /**
     * Update character currencies.
     *
     * @param Character $character
     * @param int $tier
     * @return Character
     * @throws Exception
     */
    protected function updateCharacterCurrencies(Character $character, int $tier): Character {
        $data = (new GemTierValue($tier))->maxForTier();

        $goldDust    = $character->gold_dust;
        $shards      = $character->shards;
        $copperCoins = $character->copper_coins;

        $goldDust    = $goldDust - $data['cost']['gold_dust'];
        $shards      = $shards - $data['cost']['shards'];
        $copperCoins = $copperCoins - $data['cost']['copper_coins'];

        $character->update([
            'gold'        => $goldDust > 0 ? $goldDust : 0,
            'shards'      => $shards > 0 ? $shards : 0,
            'copperCoins' => $copperCoins > 0 ? $copperCoins : 0,
        ]);

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        return $character;
    }

    /**
     * Can player craft the gem?
     *
     * @param Skill $skill
     * @param float $chance
     * @return bool
     */
    protected function canCraft(Skill $skill, float $chance): bool {
        $roll = rand(1, 100);

        $roll = $roll + $roll * $skill->skill_bonus;

        if ($roll === 1) {
            return true;
        }

        $dc = 100 - ($chance * 100);

        return $roll > $dc;
    }

    /**
     * Get the skill for crafting.
     *
     * @param Character $character
     * @return Skill
     * @throws Exception
     */
    protected function getCraftingSkill(Character $character): Skill {
        $name      = (new SkillTypeValue(SkillTypeValue::GEM_CRAFTING))->getNamedValue();
        $gameSkill = GameSkill::where('name', $name)->first();

        if (is_null($gameSkill)) {
            throw new Exception('Character is missing required game skill: ' . $name);
        }

        return $character->skills()->where('game_skill_id', $gameSkill->id)->first();
    }
}
