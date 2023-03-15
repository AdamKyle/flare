<?php

namespace App\Game\Skills\Services;

use Exception;
use App\Flare\Events\UpdateSkillEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GemBag;
use App\Flare\Models\Skill;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Skills\Builders\GemBuilder;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use App\Game\Skills\Values\GemTierValue;
use App\Game\Skills\Values\SkillTypeValue;

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

        event(new CraftedItemTimeOutEvent($character));

        $characterSkill = $this->getCraftingSkill($character);

        if ($this->skillLevelToHigh($characterSkill, $tier))

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
     * @return GemBag
     * @throws Exception
     */
    protected function giveGem(Character $character, int $tier): GemBag {
        $gem = $this->gemBuilder->buildGem($tier);

        $foundGem = $character->gemBag->gemBagSlots()->where('gem_id', $gem->id)->first();

        if (!is_null($foundGem)) {
            $foundGem->update(['amount' => $foundGem->amount + 1]);

            return;
        }

        $gemBagEntry = $character->gemBag->gemBagSlots()->create([
            'character_id' => $character->id,
            'gem_id'       => $foundGem->id,
            'amount'       => 1,
        ]);

        return $gemBagEntry;
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

        return $goldDust >= $data['gold_dust'] && $shards >= $data['shards'] && $copperCoins >= $data['copper_coins'];
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

        $goldDust    = $goldDust - $data['gold_dust'];
        $shards      = $shards - $data['shards'];
        $copperCoins = $copperCoins - $data['copper_coins'];

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
        $gameSkill = GameSkill::where('name',$name )->first();

        if (is_null($gameSkill)) {
            throw new Exception('Character is missing required game skill: ' . $name);
        }

        return $character->skills()->where('game_skill_id', $gameSkill->id)->first();
    }
}
