<?php

namespace App\Game\Skills\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\GemBagSlot;
use App\Flare\Models\Skill;
use App\Game\Character\CharacterInventory\Transformers\CharacterGemSlotsTransformer;
use App\Game\Character\CharacterSheet\Events\UpdateCharacterBaseDetailsEvent;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Events\CraftedItemTimeOutEvent;
use App\Game\Core\Events\UpdateCharacterInventoryCountEvent;
use App\Game\Core\Traits\ResponseBuilder;
use App\Game\Gems\Builders\GemBuilder;
use App\Game\Gems\Transformers\GemTransformer;
use App\Game\Gems\Values\GemTierValue;
use App\Game\Messages\Builders\ServerMessageBuilder;
use App\Game\Messages\Types\CraftingMessageTypes;
use App\Game\Skills\Events\UpdateSkillEvent;
use App\Game\Skills\Values\SkillTypeValue;
use Exception;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;

class GemService
{
    use ResponseBuilder;

    /**
     * @param GemBuilder $gemBuilder
     * @param ChanceCalculator $chanceCalculator
     * @param GemTransformer $gemTransformer
     * @param ServerMessageBuilder $serverMessageBuilder
     * @param CharacterGemSlotsTransformer $characterGemSlotsTransformer
     * @param SkillBonusService $skillBonusService
     */
    public function __construct(
        private readonly GemBuilder $gemBuilder,
        private readonly ChanceCalculator $chanceCalculator,
        private readonly GemTransformer $gemTransformer,
        private readonly ServerMessageBuilder $serverMessageBuilder,
        private readonly CharacterGemSlotsTransformer $characterGemSlotsTransformer,
        private readonly SkillBonusService $skillBonusService,
    ) {}

    /**
     * Pay for and attempt to craft a gem of the tier, adding it to the Character's gem bag on success.
     *
     * @param Character $character
     * @param int $tier
     * @return array
     */
    public function generateGem(Character $character, int $tier): array
    {
        if (! $this->canAffordCost($character, $tier)) {
            return $this->errorResult('You do not have the required currencies to craft this item.') + [
                'craft_succeeded' => false,
                'crafted_gem' => null,
                'crafted_gem_preview' => null,
            ];
        }

        if (! $character->canAddToGemBag(1)) {
            return $this->errorResult('Your Gem Bag is full. Use or remove gems before crafting more.') + [
                'craft_succeeded' => false,
                'crafted_gem' => null,
                'crafted_gem_preview' => null,
            ];
        }

        $character = $this->payForGem($character, $tier);

        event(new CraftedItemTimeOutEvent($character));

        $characterSkill = $this->getCraftingSkill($character);

        if ($this->skillLevelToHigh($characterSkill, $tier)) {
            return $this->failedCraftResult($character, 'This gem tier is too hard. You lost your investment and start to cry.');
        }

        if (! $this->canCraft($characterSkill, (new GemTierValue($tier))->maxForTier()['chance'])) {
            return $this->failedCraftResult($character, 'You failed to craft the gem, the item explodes before you into a pile of wasted effort and time.');
        }

        $gemBagEntry = $this->giveGem($character, $tier);

        if ($characterSkill->level <= (new GemTierValue($tier))->maxForTier()['max_level']) {
            event(new UpdateSkillEvent($characterSkill));
        }

        ServerMessageHandler::handleMessage($character->user, CraftingMessageTypes::CRAFTED_GEM, $gemBagEntry->gem->name, $gemBagEntry->id);

        return $this->successResult([
            'craft_succeeded' => true,
            'crafted_gem' => $this->gemTransformer->transform($gemBagEntry->gem),
            'crafted_gem_preview' => $this->characterGemSlotsTransformer->transform($gemBagEntry),
            'message' => $this->serverMessageBuilder->buildWithAdditionalInformation(CraftingMessageTypes::CRAFTED_GEM, $gemBagEntry->gem->name),
        ]);
    }

    /**
     * Return the gem tiers the Character's Gem Crafting level can craft.
     *
     * @param Character $character
     * @return array
     */
    public function getCraftableTiers(Character $character): array
    {
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
     * Return the Character's Gem Crafting XP progress.
     *
     * @param Character $character
     * @return array
     */
    public function fetchSkillXP(Character $character): array
    {
        $skill = $this->getCraftingSkill($character);

        return [
            'current_xp' => $skill->xp,
            'next_level_xp' => $skill->xp_max,
            'skill_name' => $skill->name,
            'level' => $skill->level,
        ];
    }

    /**
     * Tell the Character the gem craft failed and build the failed craft result.
     *
     * @param Character $character
     * @param string $message
     * @return array
     */
    private function failedCraftResult(Character $character, string $message): array
    {
        ServerMessageHandler::sendBasicMessage($character->user, $message);

        return $this->successResult([
            'craft_succeeded' => false,
            'crafted_gem' => null,
            'crafted_gem_preview' => null,
            'message' => $message,
        ]);
    }

    /**
     * Determine whether the Skill is below the minimum level required for the gem tier.
     *
     * @param Skill $skill
     * @param int $tier
     * @return bool
     */
    private function skillLevelToHigh(Skill $skill, int $tier): bool
    {
        return $skill->level < (new GemTierValue($tier))->maxForTier()['min_level'];
    }

    /**
     * Build a gem of the tier and add it to the Character's gem bag.
     *
     * @param Character $character
     * @param int $tier
     * @return GemBagSlot
     */
    private function giveGem(Character $character, int $tier): GemBagSlot
    {
        $gem = $this->gemBuilder->buildGem($tier);

        $gemSlot = $character->gemBag->gemSlots()->create([
            'character_id' => $character->id,
            'gem_id' => $gem->id,
            'amount' => 1,
        ]);

        event(new UpdateCharacterInventoryCountEvent($character));

        return $gemSlot;
    }

    /**
     * Determine whether the Character holds every currency the gem tier costs.
     *
     * @param Character $character
     * @param int $tier
     * @return bool
     */
    private function canAffordCost(Character $character, int $tier): bool
    {
        $cost = (new GemTierValue($tier))->maxForTier()['cost'];

        return $character->gold_dust >= $cost['gold_dust'] &&
            $character->shards >= $cost['shards'] &&
            $character->copper_coins >= $cost['copper_coins'];
    }

    /**
     * Deduct the gem tier's cost from the Character's currencies.
     *
     * @param Character $character
     * @param int $tier
     * @return Character
     */
    private function payForGem(Character $character, int $tier): Character
    {
        $cost = (new GemTierValue($tier))->maxForTier()['cost'];

        $character->update([
            'gold_dust' => $character->gold_dust - $cost['gold_dust'],
            'shards' => $character->shards - $cost['shards'],
            'copper_coins' => $character->copper_coins - $cost['copper_coins'],
        ]);

        $character = $character->refresh();

        event(new UpdateCharacterBaseDetailsEvent($character));

        return $character;
    }

    /**
     * Roll whether the gem craft succeeds, using the tier chance raised by the Skill's bonus.
     *
     * @param Skill $skill
     * @param float $chance
     * @return bool
     */
    private function canCraft(Skill $skill, float $chance): bool
    {
        if ($skill->level >= $skill->baseSkill->max_level) {
            return true;
        }

        $effectiveChance = min(1.0, $chance + $this->skillBonusService->skillBonus($skill));

        return $this->chanceCalculator->passesPercentage(floor($effectiveChance * 100));
    }

    /**
     * Return the Character's Gem Crafting Skill.
     *
     * @param Character $character
     * @return Skill
     */
    private function getCraftingSkill(Character $character): Skill
    {
        $name = SkillTypeValue::GEM_CRAFTING->getNamedValue();
        $gameSkill = GameSkill::where('name', $name)->first();
        $skill = $character->skills()->where('game_skill_id', $gameSkill->id)->first();

        if (is_null($skill)) {
            throw new Exception('Character is missing required game skill: '.$name);
        }

        return $skill;
    }
}
