<div class="flex items-center">
    @if ($value->type !== 'alchemy')
        <div class="mr-2">
            <form
                method="post"
                action="{{ route('game.market.compare.item', ['character' => $character->id, 'marketBoard' => $row->id]) }}"
            >
                @csrf
                <input type="hidden" name="item_id" value="{{ $value->id }}" />
                <input
                    type="hidden"
                    name="item_type"
                    value="{{ $value->type }}"
                />
                <x-core.buttons.success-button type="submit">
                    Compare
                </x-core.buttons.success-button>
            </form>
        </div>
    @endif

    <div>
        <form
            method="post"
            action="{{ route('game.market.buy', ['character' => $character->id]) }}"
        >
            @csrf
            <input
                type="hidden"
                name="market_board_id"
                value="{{ $row->id }}"
            />
            <x-core.buttons.primary-button type="submit">
                Buy Item
            </x-core.buttons.primary-button>
        </form>
    </div>
    @if ($row->{'character.name'} === $character->name)
        <div class="ml-2 mr-2">
            <form
                method="post"
                action="{{ route('game.delist.current-listing', ['marketBoard' => $row->id]) }}"
            >
                @csrf
                <x-core.buttons.danger-button type="submit">
                    Remove Listing
                </x-core.buttons.danger-button>
            </form>
        </div>
        <div>
            <x-core.buttons.link-buttons.primary-button
                href="{{route('game.edit.current-listings', ['marketBoard' => $row->id])}}"
            >
                Edit Listing
            </x-core.buttons.link-buttons.primary-button>
        </div>
    @endif
</div>
