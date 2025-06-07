<form
    method="post"
    action="{{ route('game.shop.sell.item', ['character' => $character->id]) }}"
>
    @csrf
    <input type="hidden" name="slot_id" value="{{ $row->id }}" />
    <x-core.buttons.success-button type="submit">
        Sell Item
    </x-core.buttons.success-button>
</form>
