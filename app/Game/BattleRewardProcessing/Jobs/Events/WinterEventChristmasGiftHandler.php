<?php

namespace App\Game\BattleRewardProcessing\Jobs\Events;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ScheduledEvent;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Builders\RandomAffixGenerator;
use App\Game\Core\Items\Builders\RandomItemDropBuilder;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Core\Items\Values\RandomAffixTier;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class WinterEventChristmasGiftHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(private int $characterId) {}

    /**
     * Handle the job
     */
    public function handle(
        RandomAffixGenerator $randomAffixGenerator,
        RandomItemDropBuilder $randomItemDropBuilder,
        RandomNumberGenerator $randomNumberGenerator,
        ChanceCalculator $chanceCalculator,
    ): void {
        $character = Character::find($this->characterId);
        $scheduledEvent = ScheduledEvent::where('event_type', EventType::WINTER_EVENT)->where('currently_running', true)->first();

        if (is_null($character)) {
            return;
        }

        if (is_null($scheduledEvent)) {
            return;
        }

        if (! $character->map->gameMap->mapType()->isTheIcePlane()) {
            return;
        }

        if ($this->canHaveWinterGift($chanceCalculator)) {

            if ($character->isInventoryFull()) {
                event(new ServerMessageEvent($character->user, 'Mr. Whiskers could not give you a christmas gift. He is sad. You made a fluffy black cat sad because your inventory is full. Make some room for next time child.'));

                return;
            }

            $typeOfGear = $this->getType($randomNumberGenerator);

            $costOfAffixToGenerate = $this->getCostOfAffixToAttach($randomNumberGenerator);

            if (is_null($typeOfGear) || ($costOfAffixToGenerate < RandomAffixTier::LEGENDARY->value)) {
                $itemToGive = $randomItemDropBuilder->generateItem($randomNumberGenerator->numberBetween(0, 400));

                $this->giveItemToPlayer($character, $itemToGive, 0);

                return;
            }

            $itemToGive = $this->fetchItemForReward($typeOfGear);

            if (is_null($itemToGive)) {
                return;
            }

            $numberOfAffixesToAttach = $this->howManyAffixesToAttach($chanceCalculator);

            $itemToGive = $this->attachAffixes($character, $randomAffixGenerator, $itemToGive, $costOfAffixToGenerate, $numberOfAffixesToAttach, $chanceCalculator);

            $this->giveItemToPlayer($character, $itemToGive, $costOfAffixToGenerate);
        }
    }

    /**
     * Can have winter gift?
     */
    private function canHaveWinterGift(ChanceCalculator $chanceCalculator): bool
    {
        return $chanceCalculator->passesPercentage(35.0);
    }

    /**
     * What type of gear do we generate?
     *
     * Null means base gear
     */
    private function getType(RandomNumberGenerator $randomNumberGenerator): ?string
    {

        $randomChance = $randomNumberGenerator->numberBetween(1, 100);

        if ($randomChance < 50) {
            return null;
        }

        $specialtyTypesOfgear = [
            ItemSpecialtyType::CORRUPTED_ICE->value,
            ItemSpecialtyType::HELL_FORGED->value,
            ItemSpecialtyType::PURGATORY_CHAINS->value,
            ItemSpecialtyType::PIRATE_LORD_LEATHER->value,
            ItemSpecialtyType::DELUSIONAL_SILVER->value,
            ItemSpecialtyType::TWISTED_EARTH->value,
            ItemSpecialtyType::FAITHLESS_PLATE->value,
            ItemSpecialtyType::PIRATE_LORD_LEATHER->value,
        ];

        return $specialtyTypesOfgear[$randomNumberGenerator->numberBetween(0, count($specialtyTypesOfgear) - 1)];
    }

    /**
     * Fetch an item to reward
     */
    private function fetchItemForReward(?string $type): Item
    {
        return Item::where('specialty_type', $type)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->whereNotIn('type', [
                'artifact',
                'trinket',
                'quest',
            ])
            ->inRandomOrder()
            ->first();
    }

    /**
     * Get cost of affix to attach.
     *
     * - Anything less then legendary will be a regular affix.
     */
    private function getCostOfAffixToAttach(RandomNumberGenerator $randomNumberGenerator): int
    {
        $randomChance = $randomNumberGenerator->numberBetween(1, 500);

        if ($randomChance >= 450) {
            return RandomAffixTier::COSMIC->value;
        }

        if ($randomChance >= 375) {
            return RandomAffixTier::MYTHIC->value;
        }

        if ($randomChance >= 100) {
            return RandomAffixTier::LEGENDARY->value;
        }

        // Anything less then a legendary
        return RandomAffixTier::LEGENDARY->value - 1;
    }

    /**
     * How many affixes should we attach?
     *
     * - 1 or 2
     */
    private function howManyAffixesToAttach(ChanceCalculator $chanceCalculator): int
    {
        return $chanceCalculator->passesPercentage(25.0) ? 2 : 1;
    }

    /**
     * Attach affixes to item
     */
    private function attachAffixes(Character $character, RandomAffixGenerator $randomAffixGenerator, Item $item, int $costOfAffix, int $numberOfAffixes, ChanceCalculator $chanceCalculator): Item
    {
        $item = $item->duplicate();

        $randomAffixGenerator = $randomAffixGenerator->setCharacter($character)->setPaidAmount($costOfAffix);

        if ($numberOfAffixes === 1) {
            $whichSide = $chanceCalculator->passesPercentage(50.0) ? 'suffix' : 'prefix';

            $item->update([
                'item_'.$whichSide.'_id' => $randomAffixGenerator->generateAffix($whichSide)->id,
                'is_mythic' => $costOfAffix === RandomAffixTier::MYTHIC->value,
                'is_cosmic' => $costOfAffix === RandomAffixTier::COSMIC->value,
            ]);

            return $item->refresh();
        }

        $item->update([
            'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
            'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
            'is_mythic' => $costOfAffix === RandomAffixTier::MYTHIC->value,
            'is_cosmic' => $costOfAffix === RandomAffixTier::COSMIC->value,
        ]);

        return $item->refresh();
    }

    /**
     * Give item to the player.
     *
     * - If cosmic announce to all chat.
     */
    private function giveItemToPlayer(Character $character, Item $item, int $costOfAffix): void
    {
        $slot = $character->inventory->slots()->create([
            'item_id' => $item->id,
        ]);

        if ($item->is_cosmic) {
            event(new GlobalMessageEvent('Holy crap! Mr. Whiskers gave a COSMIC item to: '.$character->name.' what a rare find! Kill creatures in The Ice Plane to try and earn yours while The Winter Event is running! Free christmas gifts for all while slaughtering creatures down there. Exploration works too! Do not miss out!'));
        }

        $type = match (true) {
            $costOfAffix === RandomAffixTier::LEGENDARY->value => 'Unique',
            $costOfAffix === RandomAffixTier::MYTHIC->value => 'Mythical',
            $costOfAffix === RandomAffixTier::COSMIC->value => 'Comic',
            default => 'Normal'
        };

        event(new ServerMessageEvent($character->user, 'Mr. Whiskers has given you an item child! How fun is that? You got this because it is christmas time. You unwrap your gift only to receive a ('.$type.'): '.$item->affix_name, $slot->id));
    }
}
