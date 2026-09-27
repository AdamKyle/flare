<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Models\Gem;
use App\Flare\Models\GemBagSlot;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\Item;
use App\Flare\Models\ItemSocket;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\SetSlot;
use App\Game\Character\CharacterInventory\Contracts\CurrencyCacheIssuer;
use App\Game\Core\Currency\Values\CurrencyCacheType;
use App\Game\Core\Items\Pricing\SellItemCalculator;
use App\Game\Gems\Values\GemTierValue;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\Console\Helper\ProgressBar;

class RemoveAllGemsForRebuild extends Command
{
    protected $signature = 'gem-rebuild:remove-alll-gems';

    protected $description = 'Compensates players for owned character Gems and socketed Items with Compensation Caches, then strips every socket and hard deletes every character Gem.';

    private array $characterTotals = [];

    private array $cachesCreated = [];

    private int $charactersCompensated = 0;

    private int $marketListingsDelisted = 0;

    /**
     * @param CurrencyCacheIssuer $currencyCacheIssuer
     * @param SellItemCalculator $sellItemCalculator
     */
    public function __construct(
        private readonly CurrencyCacheIssuer $currencyCacheIssuer,
        private readonly SellItemCalculator $sellItemCalculator,
    ) {
        parent::__construct();
    }

    /**
     * Compensate every Character, strip all socket state and hard delete all character Gems.
     *
     * @return int
     */
    public function handle(): int
    {
        $this->line('Starting character Gem rebuild cleanup.');
        $this->line('This command will hard delete all character Gems and remove all sockets from every Item. Equipment Items themselves will not be deleted.');

        foreach (CurrencyCacheType::cases() as $type) {
            $this->cachesCreated[$type->value] = 0;
        }

        $characterCount = Character::count();

        $this->line('Characters to inspect: '.$characterCount);
        $this->line('Compensating characters...');

        $progressBar = $this->output->createProgressBar($characterCount);

        Character::orderBy('id')->chunkById(100, fn (Collection $characters) => $this->compensateCharacterChunk($characters, $progressBar));

        $progressBar->finish();

        $this->newLine();
        $this->line('Player compensation complete.');

        $this->line('Removing old ItemSocket records...');
        $itemSocketsRemoved = ItemSocket::query()->delete();
        $this->line('ItemSocket rows removed: '.$itemSocketsRemoved);

        $this->line('Resetting socket state on Items...');
        $itemsStripped = $this->stripSocketStateFromAllItems();
        $this->line('Items stripped: '.$itemsStripped);

        $this->line('Hard deleting old character Gems...');
        $gemsDeleted = $this->hardDeleteCharacterGems();
        $this->line('Character Gems hard deleted: '.$gemsDeleted);

        $this->line('Map and Location Gems were not modified.');
        $this->newLine();
        $this->line('Character Gem rebuild cleanup complete.');

        $this->reportCounts($itemsStripped, $itemSocketsRemoved, $gemsDeleted);

        return self::SUCCESS;
    }

    /**
     * Compensate every Character in one chunk, advancing the progress bar once per Character.
     *
     * @param Collection $characters
     * @param ProgressBar $progressBar
     * @return void
     */
    private function compensateCharacterChunk(Collection $characters, ProgressBar $progressBar): void
    {
        foreach ($characters as $character) {
            $this->compensateCharacter($character);
            $progressBar->advance();
        }
    }

    /**
     * Total a Character's compensation from every owned source and issue it as Compensation Caches.
     *
     * @param Character $character
     * @return void
     */
    private function compensateCharacter(Character $character): void
    {
        $this->characterTotals = array_fill_keys(array_map(fn (CurrencyCacheType $type) => $type->value, CurrencyCacheType::cases()), 0);

        $this->compensateInventorySlots($character);
        $this->compensateSetSlots($character);
        $this->compensateMarketListings($character);
        $this->compensateGemBag($character);

        if (array_sum($this->characterTotals) === 0) {
            return;
        }

        $this->charactersCompensated++;

        foreach (CurrencyCacheType::cases() as $type) {
            $this->issueCaches($character, $type, $this->characterTotals[$type->value]);
        }
    }

    /**
     * Compensate the Item instance held in each of the Character's inventory slots.
     *
     * @param Character $character
     * @return void
     */
    private function compensateInventorySlots(Character $character): void
    {
        $inventorySlots = InventorySlot::join('inventories', 'inventories.id', '=', 'inventory_slots.inventory_id')
            ->where('inventories.character_id', $character->id)
            ->select('inventory_slots.*')
            ->with('item')
            ->get();

        foreach ($inventorySlots as $inventorySlot) {
            $this->compensateOwnedItemInstance($inventorySlot->item);
        }
    }

    /**
     * Compensate the Item instance held in each of the Character's inventory set slots.
     *
     * @param Character $character
     * @return void
     */
    private function compensateSetSlots(Character $character): void
    {
        $setSlots = SetSlot::join('inventory_sets', 'inventory_sets.id', '=', 'set_slots.inventory_set_id')
            ->where('inventory_sets.character_id', $character->id)
            ->select('set_slots.*')
            ->with('item')
            ->get();

        foreach ($setSlots as $setSlot) {
            $this->compensateOwnedItemInstance($setSlot->item);
        }
    }

    /**
     * Determine whether the Item has any socket count, socketed flag or attached socket rows.
     *
     * @param Item $item
     * @return bool
     */
    private function hasSocketState(Item $item): bool
    {
        return $item->socket_count > 0 || $item->has_gems_socketed || $item->sockets()->exists();
    }

    /**
     * Refund one socket roll and every attached character Gem for one owned Item instance with socket state.
     *
     * @param ?Item $item
     * @return void
     */
    private function compensateOwnedItemInstance(?Item $item): void
    {
        if (is_null($item) || ! $this->hasSocketState($item)) {
            return;
        }

        $this->characterTotals[CurrencyCacheType::GOLD_BARS->value] += 2000;

        foreach ($item->sockets()->with('gem')->get() as $socket) {
            $this->refundGemInstance($socket->gem, 1);
        }
    }

    /**
     * Refund affected Market Board Items at their sale value and return them to the owner's inventory.
     *
     * @param Character $character
     * @return void
     */
    private function compensateMarketListings(Character $character): void
    {
        $listings = MarketBoard::where('character_id', $character->id)->with('item')->get();

        foreach ($listings as $listing) {
            $this->compensateMarketListing($character, $listing);
        }
    }

    /**
     * Refund one affected Market Board listing and move its Item back into the owner's inventory.
     *
     * @param Character $character
     * @param MarketBoard $listing
     * @return void
     */
    private function compensateMarketListing(Character $character, MarketBoard $listing): void
    {
        if (is_null($listing->item) || ! $this->hasSocketState($listing->item)) {
            return;
        }

        $this->compensateOwnedItemInstance($listing->item);

        $this->characterTotals[CurrencyCacheType::GOLD->value] += $this->sellItemCalculator->fetchSalePriceWithAffixes($listing->item);

        $inventory = Inventory::firstOrCreate(['character_id' => $character->id]);

        InventorySlot::create([
            'inventory_id' => $inventory->id,
            'item_id' => $listing->item_id,
            'equipped' => false,
        ]);

        $listing->delete();

        $this->marketListingsDelisted++;
    }

    /**
     * Refund every loose character Gem in the Character's Gem Bag at its tier cost times the stack amount.
     *
     * @param Character $character
     * @return void
     */
    private function compensateGemBag(Character $character): void
    {
        $gemBagSlots = GemBagSlot::join('gem_bags', 'gem_bags.id', '=', 'gem_bag_slots.gem_bag_id')
            ->where('gem_bags.character_id', $character->id)
            ->select('gem_bag_slots.*')
            ->with('gem')
            ->get();

        foreach ($gemBagSlots as $gemBagSlot) {
            $this->refundGemInstance($gemBagSlot->gem, $gemBagSlot->amount);
        }
    }

    /**
     * Add the tier crafting cost of a character-domain Gem instance multiplied by amount.
     *
     * @param ?Gem $gem
     * @param int $amount
     * @return void
     */
    private function refundGemInstance(?Gem $gem, int $amount): void
    {
        if (is_null($gem) || $gem->domain !== Gem::DOMAIN_CHARACTER) {
            return;
        }

        $cost = (new GemTierValue($gem->tier))->maxForTier()['cost'];

        $this->characterTotals[CurrencyCacheType::GOLD_DUST->value] += $cost['gold_dust'] * $amount;
        $this->characterTotals[CurrencyCacheType::SHARDS->value] += $cost['shards'] * $amount;
        $this->characterTotals[CurrencyCacheType::COPPER_COINS->value] += $cost['copper_coins'] * $amount;
    }

    /**
     * Issue Compensation Caches for a positive total and record how many the Character Inventory module created.
     *
     * @param Character $character
     * @param CurrencyCacheType $type
     * @param int $totalAmount
     * @return void
     */
    private function issueCaches(Character $character, CurrencyCacheType $type, int $totalAmount): void
    {
        if ($totalAmount <= 0) {
            return;
        }

        $created = $this->currencyCacheIssuer->issue(
            $character->id,
            $type,
            $totalAmount,
        );

        $this->cachesCreated[$type->value] += $created;
    }

    /**
     * Reset the socket count and socketed flag on every Item that still carries socket state.
     *
     * @return int
     */
    private function stripSocketStateFromAllItems(): int
    {
        return Item::where(function ($query) {
            $query->where('socket_count', '>', 0)->orWhere('has_gems_socketed', true);
        })->update([
            'socket_count' => 0,
            'has_gems_socketed' => false,
        ]);
    }

    /**
     * Remove Gem Bag slots holding character Gems, then physically delete every character-domain Gem.
     *
     * @return int
     */
    private function hardDeleteCharacterGems(): int
    {
        GemBagSlot::whereIn('gem_id', Gem::where('domain', Gem::DOMAIN_CHARACTER)->select('id'))->delete();

        return Gem::where('domain', Gem::DOMAIN_CHARACTER)->delete();
    }

    /**
     * Print the concise command summary counts.
     *
     * @param int $itemsStripped
     * @param int $itemSocketsRemoved
     * @param int $gemsDeleted
     * @return void
     */
    private function reportCounts(int $itemsStripped, int $itemSocketsRemoved, int $gemsDeleted): void
    {
        $this->line('Characters compensated: '.$this->charactersCompensated);
        $this->line('Market listings delisted: '.$this->marketListingsDelisted);
        $this->line('Items stripped: '.$itemsStripped);
        $this->line('ItemSocket rows removed: '.$itemSocketsRemoved);
        $this->line('Character Gems hard deleted: '.$gemsDeleted);

        foreach (CurrencyCacheType::cases() as $type) {
            $this->line($type->label().' Compensation Caches created: '.$this->cachesCreated[$type->value]);
        }
    }
}
