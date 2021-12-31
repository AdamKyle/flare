<?php

namespace App\Game\Core\Controllers;

use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Core\Services\ComparisonService;
use App\Game\Skills\Events\UpdateCharacterEnchantingList;
use App\Game\Skills\Services\EnchantingService;
use Cache;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\InventorySlot;
use App\Flare\Models\User;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Game\Core\Events\CharacterInventoryUpdateBroadCastEvent;
use App\Game\Core\Events\UpdateAttackStats;
use App\Game\Core\Services\InventorySetService;
use App\Game\Core\Services\EquipItemService;
use App\Game\Core\Exceptions\EquipItemException;
use App\Game\Core\Requests\ComparisonValidation;
use App\Game\Core\Requests\EquipItemValidation;
use App\Game\Core\Services\CharacterInventoryService;
use App\Game\Core\Values\ValidEquipPositionsValue;

class CharacterInventoryController extends Controller {

    private $equipItemService;

    private $characterTransformer;

    private $buildCharacterAttackTypes;

    private $manager;

    public function __construct(
        EquipItemService $equipItemService,
        BuildCharacterAttackTypes $buildCharacterAttackTypes,
        CharacterAttackTransformer $characterTransformer,
        EnchantingService $enchantingService,
        Manager $manager
    ) {

        $this->equipItemService          = $equipItemService;
        $this->characterTransformer      = $characterTransformer;
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
        $this->enchantingService         = $enchantingService;
        $this->manager                   = $manager;

        $this->middleware('auth');

        $this->middleware('is.character.dead');

        $this->middleware('is.character.adventuring');
    }

    public function compare(
        ComparisonValidation $request,
        ComparisonService $comparisonService,
        Character $character
    ) {

        $itemToEquip = InventorySlot::find($request->slot_id);

        if (is_null($itemToEquip)) {
            return redirect()->back()->with('error', 'Item not found in your inventory.');
        }

        if ($comparisonService->isItemUnique($itemToEquip)) {
            if ($comparisonService->characterHasUniqueEquipped($character)) {
                return redirect()->back()->with('error', 'You cannot equip anymore unique items.');
            }
        }

        $type = $request->item_to_equip_type;

        if ($type === 'spell-healing' || $type === 'spell-damage') {
            $type = 'spell';
        }

        $comparisonService->buildComparisonData($character, $itemToEquip, $type);

        return redirect()->to(route('game.inventory.compare-items', ['user' => $character->user, 'slot' => $itemToEquip->id]));
    }

    public function compareItem(Request $request, User $user) {
        if (!$request->has('slot')) {
            return redirect()->route('game.character.sheet')->with('error', 'You are not allowed to do that.');
        }

        if (!Cache::has($user->id . '-compareItemDetails' . $request->slot)) {
            return redirect()->route('game.character.sheet')->with('error', 'Item comparison expired.');
        }

        $cache           = Cache::get($user->id . '-compareItemDetails' . $request->slot);
        $cache['isShop'] = false;

        return view('game.character.equipment', $cache);
    }

    public function equipItem(EquipItemValidation $request, Character $character) {
        try {
            $item = $this->equipItemService->setRequest($request)
                                           ->setCharacter($character)
                                           ->replaceItem();

            $this->updateCharacterAttakDataCache($character);

            event(new CharacterInventoryUpdateBroadCastEvent($character->user));

            $affixData = $this->enchantingService->fetchAffixes($character->refresh());

            event(new UpdateCharacterEnchantingList(
                $character->user,
                $affixData['affixes'],
                $affixData['character_inventory'],
            ));

            return redirect()->to(route('game.character.sheet'))->with('success', $item->affix_name . ' Equipped.');

        } catch(EquipItemException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    protected function updateCharacterAttakDataCache(Character $character) {
        $this->buildCharacterAttackTypes->buildCache($character);

        $characterData = new ResourceItem($character->refresh(), $this->characterTransformer);

        $characterData = $this->manager->createData($characterData)->toArray();

        event(new UpdateAttackStats($characterData, $character->user));
    }
}
