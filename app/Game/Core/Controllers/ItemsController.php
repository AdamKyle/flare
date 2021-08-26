<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Game\Core\Requests\UseMultipleItemsRequest;
use App\Game\Core\Services\UseItemService;
use App\Game\Skills\Values\SkillTypeValue;
use App\Http\Controllers\Controller;
use League\Fractal\Manager;
use App\Flare\Transformers\CharacterAttackTransformer;
use App\Flare\Models\Item;
use App\Flare\Traits\Controllers\ItemsShowInformation;


class ItemsController extends Controller {

    use ItemsShowInformation;

    /**
     * @var Manager $manager
     */
    private $useItemService;

    /**
     * ItemsController constructor.
     *
     * @param UseItemService $useItemService
     */
    public function  __construct(UseItemService $useItemService) {
        $this->useItemService = $useItemService;
    }

    public function show(Item $item) {
        return $this->renderItemShow('game.items.item', $item);
    }

    public function useItem(Character $character, Item $item) {
        if ($character->boons->count() === 10) {
            return redirect()->back()->with('error', 'You can only have a max of ten boons applied. Check active boons to see which ones you have. You can always cancel one by clicking on the row.');
        }

        $slot = $character->inventory->slots->filter(function($slot) use($item) {
           return $slot->item_id === $item->id;
        })->first();

        if (is_null($slot)) {
            return redirect()->back()->with('error', 'You don\'t have this item.');
        }

        $this->useItemService->useItem($slot, $character, $item);

        return redirect()->back()->with('success', 'Applied: ' . $item->name . ' for: ' . $item->lasts_for . ' Minutes.');
    }
}
