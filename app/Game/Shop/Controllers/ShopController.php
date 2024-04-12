<?php

namespace App\Game\Shop\Controllers;

use App\Flare\Models\Character;
use App\Flare\Models\Location;
use App\Flare\Transformers\CharacterSheetBaseInfoTransformer;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterInventory\Services\EquipItemService;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Game\Shop\Services\ShopService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use League\Fractal\Manager;


class ShopController extends Controller {

    private EquipItemService $equipItemService;

    private BuildCharacterAttackTypes $buildCharacterAttackTypes;

    private CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer;

    private ShopService $shopService;

    private Manager $manager;

    public function __construct(
        EquipItemService $equipItemService,
        BuildCharacterAttackTypes $buildCharacterAttackTypes,
        CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
        ShopService $shopService,
        Manager $manager
    ) {
        $this->middleware('auth');
        $this->middleware('is.character.dead');

        $this->equipItemService                       = $equipItemService;
        $this->buildCharacterAttackTypes              = $buildCharacterAttackTypes;
        $this->characterSheetBaseInfoTransformer      = $characterSheetBaseInfoTransformer;
        $this->shopService                            = $shopService;
        $this->manager                                = $manager;
    }

    public function shopBuy(Character $character) {

        $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();

        return view('game.shop.buy', [
            'isLocation' => !is_null($location),
            'gold'       => $character->gold,
            'character'  => $character,
        ]);
    }

    public function shopSell(Character $character) {

        $location = Location::where('x', $character->map->character_position_x)->where('y', $character->map->character_position_y)->first();

        return view('game.shop.sell', [
            'isLocation' => !is_null($location),
            'gold'       => $character->gold,
            'character'  => $character,
        ]);
    }

    public function shopSellAll(Character $character) {

        $totalSoldFor = $this->shopService->sellAllItemsInInventory($character);

        $newGold = $character->gold + $totalSoldFor;

        if ($newGold > MaxCurrenciesValue::MAX_GOLD) {
            $newGold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'gold' => $newGold,
        ]);

        if ($totalSoldFor === 0) {
            return redirect()->back()->with('error', 'You have nothing that you can sell.');
        }

        $character = $character->refresh();

        event(new UpdateTopBarEvent($character));

        return redirect()->back()->with('success', 'Sold all your unequipped items for a total of: ' . $totalSoldFor . ' gold.');
    }



    public function sell(Request $request, Character $character) {

        $inventorySlot = $character->inventory->slots->filter(function ($slot) use ($request) {
            return $slot->id === (int) $request->slot_id && !$slot->equipped;
        })->first();

        if (is_null($inventorySlot)) {
            return redirect()->back()->with('error', 'Item not found.');
        }

        $item         = $inventorySlot->item;
        $totalSoldFor = $this->shopService->sellItem($inventorySlot, $character);

        return redirect()->back()->with('success', 'Sold: ' . $item->affix_name . ' for: ' . $totalSoldFor . ' gold.');
    }


}
