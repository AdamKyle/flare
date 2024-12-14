<?php

namespace App\Game\BattleRewardProcessing\Jobs\Events;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Facades\App\Flare\RandomNumber\RandomNumberGenerator;
use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\ScheduledEvent;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;

class WinterEventChristmasGiftHandler implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param integer $characterId
     */
    public function __construct(private int $characterId) {}

    /**
     * Handle the job
     *
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param RandomItemDropBuilder $randomItemDropBuilder
     * @return void
     */
    public function handle(RandomAffixGenerator $randomAffixGenerator, RandomItemDropBuilder $randomItemDropBuilder): void
    {
        $character = Character::find($this->characterId);
        $scheduledEvent = ScheduledEvent::where('event_type', EventType::WINTER_EVENT)->where('currently_running', true)->first();

        if (is_null($character)) {
            return;
        }

        if (is_null($scheduledEvent)) {
            return;
        }

        if (!$character->map->gameMap->mapType()->isTheIcePlane()) {
            return;
        }

        if ($this->canHaveWinterGift()) {

            if ($character->isInventoryFull()) {
                event(new ServerMessageEvent($character->user, 'Mr. Whiskers could not give you a christmas gift. He is sad. You made a fluffy black cat sad because your inventory is full. Make some room for next time child.'));

                return;
            }

            $typeOfGear = $this->getType();

            $costOfAffixToGenerate = $this->getCostOfAffixToAttach();

            if (is_null($typeOfGear) || ($costOfAffixToGenerate < RandomAffixDetails::LEGENDARY)) {
                $itemToGive = $randomItemDropBuilder->generateItem(rand(0, 400));

                $this->giveItemToPlayer($character, $itemToGive, 0);

                return;
            }

            $itemToGive = $this->fetchItemForReward($typeOfGear);

            if (is_null($itemToGive)) {
                return;
            }

            $numberOfAffixesToAttach = $this->howManyAffixesToAttach();

            $itemToGive = $this->attachAffixes($character, $randomAffixGenerator, $itemToGive, $costOfAffixToGenerate, $numberOfAffixesToAttach);

            $this->giveItemToPlayer($character, $itemToGive, $costOfAffixToGenerate);
        }
    }

    /**
     * Can have winter gift?
     *
     * @return boolean
     */
    private function canHaveWinterGift(): bool
    {
        return RandomNumberGenerator::generateTrueRandomNumber(100) > 65;
    }

    /**
     * What type of gear do we generate?
     *
     * Null means base gear
     *
     * @return string|null
     */
    private function getType(): ?string
    {

        $randomChance = RandomNumberGenerator::generateTrueRandomNumber(100);

        if ($randomChance < 50) {
            return null;
        }

        $specialtyTypesOfgear = [
            ItemSpecialtyType::CORRUPTED_ICE,
            ItemSpecialtyType::HELL_FORGED,
            ItemSpecialtyType::PURGATORY_CHAINS,
            ItemSpecialtyType::PIRATE_LORD_LEATHER,
            ItemSpecialtyType::DELUSIONAL_SILVER,
            ItemSpecialtyType::TWISTED_EARTH,
            ItemSpecialtyType::FAITHLESS_PLATE,
            ItemSpecialtyType::PIRATE_LORD_LEATHER,
        ];

        return $specialtyTypesOfgear[rand(0, count($specialtyTypesOfgear) - 1)];
    }

    /**
     * Fetch an item to reward
     *
     * @param string|null $type
     * @return Item
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
     *
     * @return integer
     */
    private function getCostOfAffixToAttach(): int
    {
        $randomChance = RandomNumberGenerator::generateTrueRandomNumber(500);

        if ($randomChance >= 450) {
            return RandomAffixDetails::COSMIC;
        }

        if ($randomChance >= 375) {
            return RandomAffixDetails::MYTHIC;
        }

        if ($randomChance >= 100) {
            return RandomAffixDetails::LEGENDARY;
        }

        // Anything less then a legendary
        return RandomAffixDetails::LEGENDARY - 1;
    }

    /**
     * How many affixes should we attach?
     *
     * - 1 or 2
     *
     * @return integer
     */
    private function howManyAffixesToAttach(): int
    {
        return rand(1, 100) > 75 ? 2 : 1;
    }

    /**
     * Attach affixes to item
     *
     * @param Character $character
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param Item $item
     * @param integer $costOfAffix
     * @param integer $numberOfAffixes
     * @return Item
     */
    private function attachAffixes(Character $character, RandomAffixGenerator $randomAffixGenerator, Item $item, int $costOfAffix, int $numberOfAffixes): Item
    {
        $item = $item->duplicate();

        $randomAffixGenerator = $randomAffixGenerator->setCharacter($character)->setPaidAmount($costOfAffix);

        if ($numberOfAffixes === 1) {
            $whichSide = rand(1, 100) > 50 ? 'suffix' : 'prefix';

            $item->update([
                'item_' . $whichSide . '_id' => $randomAffixGenerator->generateAffix($whichSide)->id,
                'is_mythic' => $costOfAffix === RandomAffixDetails::MYTHIC,
                'is_cosmic' => $costOfAffix === RandomAffixDetails::COSMIC,
            ]);

            return $item->refresh();
        }

        $item->update([
            'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
            'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
            'is_mythic' => $costOfAffix === RandomAffixDetails::MYTHIC,
            'is_cosmic' => $costOfAffix === RandomAffixDetails::COSMIC,
        ]);

        return $item->refresh();
    }

    /**
     * Give item to the player.
     *
     * - If cosmic announce to all of chat.
     *
     * @param Character $character
     * @param Item $item
     * @return void
     */
    private function giveItemToPlayer(Character $character, Item $item, int $costOfAffix): void
    {
        $slot = $character->inventory->slots()->create([
            'item_id' => $item->id,
        ]);

        if ($item->is_cosmic) {
            event(new GlobalMessageEvent('Holy crap! Mr. Whiskers gave a COSMIC item to: ' . $character->name . ' what a rare fine! Kill creatures in The Ice Plane to try and earn yours while The Winter Event is running! Free christmas gifts for all while slaughtering creatures down there. Exploration works too! Do not miss out!'));
        }

        $type = match (true) {
            $costOfAffix === RandomAffixDetails::LEGENDARY => 'Unique',
            $costOfAffix === RandomAffixDetails::MYTHIC => 'Mythical',
            $costOfAffix === RandomAffixDetails::COSMIC => 'Comic',
            default => 'Normal'
        };

        event(new ServerMessageEvent($character->user, 'Mr. Whiskers has given you an item child! How fun is that? You got this because it is christmas time. You ununwrap your gift only to recieve a (' . $type . '): ' . $item->affix_name, $slot->id));
    }
}
