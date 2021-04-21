<div class="card">
    <div class="card-body">
        <h4 class="card-title">Equipped: <x-item-display-color :item="$value['slot']->item" /></h4>
        @if (!empty($equipment))
            @include('game.character.partials.equipment.sections.currently-equipped', [
                'details' => $equipment
            ])
            <h6>Stat Details</h6>
            @include('game.character.partials.equipment.sections.item-stat-details', ['item' => $equipment['slot']->item])
        @endif
    </div>
</div>
