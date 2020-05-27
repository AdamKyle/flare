<h6>Item Details</h6>
<dl>
    <dt>Name:</dt>
    <dd>{{$item->name}}</dd>
    <dt>Base Damage:</dt>
    <dd>{{$item->base_damage}}</dd>
    <dt>Base AC:</dt>
    <dd>{{$item->base_ac}}</dd>
    <dt>Type:</dt>
    <dd>{{$item->type}}</dd>
</dl>
<hr />

<h6>Item Affixes</h6>
@if (is_null($item->itemPrefix) && is_null($item->itemSuffix))
    <div class="alert alert-info">
        There are no affixes on this item.
    </div>
@else
    @if (!is_null($item->prefix))
        <dl>
            <dt>Name:</dt>
            <dd>{{$item->itemPrefix->name}}</dd>
            <dt>Base Damage:</dt>
            <dd>{{$item->itemPrefix->base_damage_mod * 100}}%</dd>
            <dt>Base Ac:</dt>
            <dd>{{$item->itemPrefix->base_ac_mod * 100}}%</dd>
            <dt>Description:</dt>
            <dd>{{$item->itemPrefix->description}}</dd>
        </dl>
    @endif
    @if (!is_null($item->prefix))
        <dl>
            <dt>Name:</dt>
            <dd>{{$item->itemSuffix->name}}</dd>
            <dt>Base Damage:</dt>
            <dd>{{$item->itemSuffix->base_damage_mod * 100}}%</dd>
            <dt>Description:</dt>
            <dd>{{$item->itemSuffix->description * 100}}%</dd>
        </dl>
    @endif
@endif
<hr />
<h6>Increases Stats By:</h6>
@if (empty($details))
    @include('game.core.partials.equip.details.item-stat-details', ['item' => $item])
@else
    @if (!is_null($item->default_position))
    @include('game.core.partials.equip.details.stat-details', ['details' => $details, 'hasDefaultPosition' => true])
    @else
        <div class="row">
            @if (count($details) >= 1)
                @include('game.core.partials.equip.details.stat-details', ['details' => $details, 'hasDefaultPosition' => false])
            @else
                <div class="col-md-6">
                    @include('game.core.partials.equip.details.stat-details', ['details' => $details, 'hasDefaultPosition' => false])
                </div>

                <div class="col-md-6">
                    <p><strong>If equipped as second item:</strong></p>
                    @include('game.core.partials.equip.details.item-stat-details', ['item' => $item])
                </div>
            @endif
        </div>
    @endif
    
@endif
<hr />
