<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Item;
use App\Game\Core\Items\Services\ItemShowInformationService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class ItemsController extends Controller
{
    public function __construct(private readonly ItemShowInformationService $itemShowInformationService) {}

    /**
     * Show the Game item details page for the given Item.
     *
     * @return View
     */
    public function show(Item $item)
    {
        return view('game.items.item', $this->itemShowInformationService->details($item));
    }
}
