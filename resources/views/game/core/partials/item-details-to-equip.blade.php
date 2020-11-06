@include('game.items.partials.item-details', ['item' => $item])
<hr />
<h6>Stat Details:</h6>
@if (empty($details))
    @include('game.core.partials.equip.details.item-stat-details', ['item' => $item])
@else
    @if (!is_null($item->default_position))
        @include('game.core.partials.equip.details.stat-details', ['details' => $details, 'hasDefaultPosition' => true])
    @else
        <div class="row">
            <div class="col-md-6">
                @include('game.core.partials.equip.details.stat-details', ['details' => $details, 'hasDefaultPosition' => false])
            </div>

            <div class="col-md-6">
                <p class="mt-4">If equipped as second item:</p>
                @include('game.core.partials.equip.details.item-stat-details', ['item' => $item])
            </div>
        </div>
    @endif
    
@endif
<hr />
