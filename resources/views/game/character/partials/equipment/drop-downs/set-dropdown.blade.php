<div class="dropdown">
    <button class="btn btn-primary dropdown-toggle" type="button" id="actionsButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        Actions
    </button>
    <div class="dropdown-menu" aria-labelledby="actionsButton">
        <form id="remove-from-set-{{$slot->id}}" action="{{route('game.remove.from.set', ['character' => $character])}}" method="POST" style="display: none">
            @csrf

            <input type="hidden" name="slot_id" value="{{$slot->id}}" />
            <input type="hidden" name="inventory_set_id" value="{{$inventorySet->id}}" />
        </form>

        <a class="dropdown-item" href="#"
           onclick="event.preventDefault();
               document.getElementById('remove-from-set-{{$slot->id}}').submit();">
            {{ __('Remove from set') }}
        </a>

    </div>
</div>
