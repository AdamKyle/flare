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
    <x-core.buttons.link-buttons.primary-button>
        Buy Item
    </x-core.buttons.link-buttons.primary-button>
    </div>
</div>
