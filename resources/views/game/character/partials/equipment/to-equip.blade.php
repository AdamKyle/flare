<div class="card">
    <div class="card-body">
        <h4 class="card-title">To Equip: <x-item-display-color :item="$itemToEquip" /> </h4>
        <hr />
        @include('game.character.partials.equipment.sections.item-details-to-equip', [
            'item'         => $itemToEquip,
            'details'      => $details,
        ])

        <form class="mt-4" action="{{route('game.equip.item', ['character' => $characterId])}}" method="POST">
            @csrf
            @include('game.character.partials.equipment.sections.equip.' . $type, [
                'slotId'      => $slotId,
                'details'     => $details,
                'itemToEquip' => $itemToEquip,
                'type'        => $type
            ])
            <button type="submit" class="btn btn-primary">Equip</button>
        </form>
    </div>
</div>
