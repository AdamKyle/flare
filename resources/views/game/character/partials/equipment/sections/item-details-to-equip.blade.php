@include('game.items.partials.item-details', ['item' => $item])
<hr />
<h6>Stat Details:</h6>
<p>These stat increases so <span class="text-success">green for any increase</span> and <span class="text-danger"> red for any decrease</span></p>
@if (empty($details))
    @include('game.character.partials.equipment.sections.equip.details.item-stat-details', ['item' => $item])
@else
    @if (!is_null($item->default_position))
        @include('game.character.partials.equipment.sections.equip.details.stat-details', ['details' => $details, 'hasDefaultPosition' => true])
    @else
        <div class="row">
            <div class="col-md-6">
                @include('game.character.partials.equipment.sections.equip.details.stat-details', ['details' => $details, 'hasDefaultPosition' => false])
            </div>

            <div class="col-md-6">
                <p class="mt-4">If equipped as second item:</p>
                @include('game.character.partials.equipment.sections.equip.details.item-stat-details', ['item' => $item])
            </div>
        </div>
    @endif

@endif
<hr />
