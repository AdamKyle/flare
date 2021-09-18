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
    @if (!is_null($details['replaces_item']->itemPrefix))
        <dl>
            <dt>Name:</dt>
            <dd>{{$details['replaces_item']->itemPrefix->name}}</dd>
            <dt>Base Attack Bonus:</dt>
            <dd>{{$details['replaces_item']->itemPrefix->base_damage_mod * 100}}%</dd>
            <dt>Base Damage Modifier (affects skills):</dt>
            <dd>{{$item->itemPrefix->base_damage_mod_bonus * 100}}%</dd>
            <dt>Base AC:</dt>
            <dd>{{$details['replaces_item']->itemPrefix->base_ac_mod * 100}}%</dd>
            <dt>Description:</dt>
            <dd>{{$details['replaces_item']->itemPrefix->description}}</dd>
        </dl>
    @endif
    @if (!is_null($details['replaces_item']->itemPrefix))
        <dl>
            <dt>Name:</dt>
            <dd>{{$details['replaces_item']->itemSuffix->name}}</dd>
            <dt>Base Attack Bonus:</dt>
            <dd>{{$details['replaces_item']->itemPrefix->base_damage_mod * 100}}%</dd>
            <dt>Base Damage Modifier (affects skills):</dt>
            <dd>{{$item->itemPrefix->base_damage_mod_bonus * 100}}%</dd>
            <dt>Base AC:</dt>
            <dd>{{$details['replaces_item']->itemSuffix->base_ac_mod * 100}}%</dd>
            <dt>Description:</dt>
            <dd>{{$details['replaces_item']->itemSuffix->description}}</dd>
        </dl>
    @endif
@endif