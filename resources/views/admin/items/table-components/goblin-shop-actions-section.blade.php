<div class="flex items-center">
    <div class="mr-2">
        <form method='post' action="{{route('game.goblin-shop.buy', ['character' => $character->id, 'item' => App\Flare\Models\Item::where('name', $row->name)->first()->id])}}">
            @csrf
            <x-core.buttons.primary-button type="submit">
                Buy Item
            </x-core.buttons.primary-button>
        </form>
    </div>
</div>
