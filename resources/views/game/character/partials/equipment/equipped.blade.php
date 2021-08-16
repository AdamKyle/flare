<div class="card">
    <div class="card-body">
        <h4 class="card-title">Equipped: <x-item-display-color :item="$value['slot']->item" /></h4>
        @if (!empty($equipment))
            @include('game.character.partials.equipment.sections.currently-equipped', [
                'details' => $equipment
            ])
            <h6 class="mb-4">Stat Details:</h6>
            @include('game.character.partials.equipment.sections.item-stat-details', ['item' => $equipment['slot']->item])
        @endif

        <p class="mt-3 mb-3">
            <sup>*</sup> Attack includes Base Attack Modifier applied automatically, rounded to the nearest whole number.
        </p>
        <p>
            <sup>**</sup> Applies to all skills that increase this modifier.
        </p>
        @if ($equipment['slot']->item->can_resurrect)
            <p>
                <sup>rc</sup> Used to determine, upon death in either battle or adventure, if your character can automatically resurrect and heal.
            </p>
        @endif

    </div>
</div>
