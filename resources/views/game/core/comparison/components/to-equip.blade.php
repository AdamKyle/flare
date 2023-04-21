@include('game.items.components.item-details', ['item' => \App\Flare\Models\Item::find($item['id']), 'isShop' => $isShop])
