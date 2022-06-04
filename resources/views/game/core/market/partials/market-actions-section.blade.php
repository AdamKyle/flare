
<div class="flex items-center">
    <div class="mr-2">
        <form method='post' action="{{route('game.market.compare.item', ['character' => $character->id, 'marketBoard' => $row->id])}}">
            @csrf
            <input type="hidden" name="item_id" value="{{$value->id}}" />
            <input type="hidden" name="item_type" value="{{$value->type}}" />
            <x-core.buttons.success-button type="submit">
                Compare
            </x-core.buttons.success-button>
        </form>
    </div>
    <div>
        <form method='post' action="{{route('game.market.buy', ['character' => $character->id])}}">
            @csrf
            <input type="hidden" name="market_board_id" value="{{$row->id}}" />
            <x-core.buttons.primary-button type="submit">
                Buy Item
            </x-core.buttons.primary-button>
        </form>
    </div>
</div>
