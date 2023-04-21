<a class="btn btn-primary" href="{{route('game.shop.sell.item', ['character' => $character->id])}}"
   onclick="event.preventDefault();
       document.getElementById('shop-sell-form-slot-{{$slot->id}}').submit();">
    {{ __('Sell') }}
</a>

<form id="shop-sell-form-slot-{{$slot->id}}" action="{{route('game.shop.sell.item', ['character' => $character->id])}}" method="POST" style="display: none;">
    @csrf

    <input type="hidden" name="slot_id" value={{$slot->id}} />
</form>
