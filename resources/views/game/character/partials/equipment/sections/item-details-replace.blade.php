<div class="alert alert-success">
    Replacing this weapon will increase the attack by: {{$details['damage_adjustment']}}.
</div>
<h6>Item Details</h6>
<dl>
    <dt>Name:</dt>
    <dd>{{$details['replaces_item']->name}}</dd>
    <dt>Base Damage:</dt>
    <dd>{{$details['replaces_item']->getTotalDamage()}} <em>(With all modifiers)</em></dd>
    <dt>Position:</dt>
    <dd>{{title_case(str_replace('-', ' ', $details['slot']->position))}}</dd>
</dl>
<hr />

<h6>Item Affixes</h6>
@if (is_null($details['replaces_item']->itemPrefix) && is_null($details['replaces_item']->itemSuffix))
    <div class="alert alert-info">
        There are no affixes on this item.
    </div>
@else
    <div class="row">
        <div class={{!is_null($details['replaces_item']->itemSuffix) ? 'col-md-6' : 'col-md-12'}}>
            @if (!is_null($details['replaces_item']->itemPrefix))
                <hr />
                @include('game.items.partials.item-prefix', ['item' => $details['replaces_item']])
            @endif
        </div>
        <div class={{!is_null($details['replaces_item']->itemPrefix) ? 'col-md-6' : 'col-md-12'}}>
            @if (!is_null($details['replaces_item']->itemSuffix))
                <hr />
                @include('game.items.partials.item-suffix', ['item' => $details['replaces_item']])
            @endif
        </div>
    </div>
@endif