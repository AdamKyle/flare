<div class="flex items-center">
    <div class="mr-2">
        <form method='post' action="{{route('game.shop.compare.item', ['character' => $character->id])}}">
            @csrf
            <input type="hidden" name="item_name" value="{{$row->name}}" />
            <input type="hidden" name="item_type" value="{{$row->type}}" />
            <x-core.buttons.success-button type="submit">
                Compare
            </x-core.buttons.success-button>
        </form>
    </div>
    <div>
        <form method='post' action="{{route('game.shop.buy.item', ['character' => $character->id])}}">
            @csrf
            <input type="hidden" name="item_id" value="{{App\Flare\Models\Item::where('name', $row->name)->first()->id}}" />
            <x-core.buttons.primary-button type="submit">
                Buy Item
            </x-core.buttons.primary-button>
        </form>
    </div>
</div>
