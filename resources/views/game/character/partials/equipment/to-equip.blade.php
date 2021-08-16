<div class="card">
    <div class="card-body">
        <h4 class="card-title">To Equip: <x-item-display-color :item="$itemToEquip" /> </h4>
        <hr />
        @include('game.character.partials.equipment.sections.item-details-to-equip', [
            'item'         => $itemToEquip,
            'details'      => $details,
        ])

        <p class="mt-3 mb-3">
            <sup>*</sup> Attack includes Base Attack Modifier applied automatically, rounded to the nearest whole number.
        </p>
        <p>
            <sup>**</sup> Applies to all skills that increase this modifier.
        </p>
        @if ($itemToEquip->can_resurrect)
            <p>
                <sup>rc</sup> Used to determine, upon death in either battle or adventure, if your character can automatically resurrect and heal.
            </p>
        @endif


        <form class="mt-4" action="{{route('game.equip.item', ['character' => $characterId])}}" method="POST">
            @csrf

            @if ($bowEquipped)
                <div class="alert alert-warning mt-2 mb-3">
                    You already have a bow equipped, remember you cannot duel wield bows with any weapon or shield. Equipping this item
                    will replace the currently equipped bow.
                </div>
            @endif

            @include('game.character.partials.equipment.sections.equip.' . ($itemToEquip->type === 'bow' ? 'weapon' : $type), [
                'slotId'      => $slotId,
                'details'     => $details,
                'itemToEquip' => $itemToEquip,
                'type'        => $type,
                'item'        => $itemToEquip,
            ])
            <button type="submit" class="btn btn-primary">Equip</button>
        </form>
    </div>
</div>
