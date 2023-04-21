@php
    $characterInventoryCount = $inventorySet->character->inventory->slots()->count();
    $characterInventoryMax   = $inventorySet->character->inventory_max;
    $icon                    = 'text-warning fas fa-exclamation-triangle';
    $getAll                  = false;
    $disabled                = false;

    if ($inventorySet->slots->count() < $characterInventoryMax) {
        $icon   = 'text-success fas fa-check-circle';
        $getAll = true;
    }

    if ($characterInventoryCount === 75) {
        $icon     = 'text-danger fas fa-exclamation-circle';
        $disabled = true;
    }

@endphp

<div class="modal" id="character-inventory-set-{{$inventorySet->id}}" tabindex="-1" role="dialog" aria-labelledby="UseLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Empty Set <i class="{{$icon}}"></i></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>
                    Clearing a set removes all the items from it, if you only want to remove one or two items,
                    you can use the drop down beside the items in question.
                </p>

                @if (!$getAll)
                    <div class="alert alert-warning mt-2 mb-3">
                        You do not have the inventory space to take all these items out. We will take what we can
                        and leave the rest. If you are fine with that, please continue by clicking: Clear set.
                    </div>
                @endif

                @if ($disabled)
                    <div class="alert alert-warning mt-2 mb-3">
                        You do not have the inventory space to clear this set.
                    </div>
                @endif
               <form method="POST" action="{{route('game.inventory.empty.set', [
                  'character' => $inventorySet->character,
                  'inventorySet' => $inventorySet,
               ])}}">
                   @csrf

                   <button type="submit" class="btn btn-primary mb-2" {{$disabled ? 'disabled' : ''}}>
                       Clear Set
                   </button>
               </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
