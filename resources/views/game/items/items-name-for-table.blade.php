<a href="{{ route('game.items.item', ['item' => $value->id]) }}">
    <x-item-display-color :item="$value" />
</a>
