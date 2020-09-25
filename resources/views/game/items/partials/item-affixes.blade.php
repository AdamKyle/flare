@if (is_null($item->itemPrefix) && is_null($item->itemSuffix))
    <div class="alert alert-info">
        There are no affixes on this item.
    </div>
@else
    @if (!is_null($item->itemPrefix))
    <h6 class="mb-2">Prefix</h5>
        <p>{{$item->itemPrefix->description}}</p>
        <hr />
        <dl>
            <dt>Name:</dt>
            <dd>{{$item->itemPrefix->name}}</dd>
            <dt>Base Damage:</dt>
            <dd>{{$item->itemPrefix->base_damage_mod * 100}}%</dd>
            <dt>Base Healing Mod:</dt>
            <dd>{{$item->itemPrefix->base_healing_mod * 100}}%</dd>
            <dt>Str Modifier:</dt>
            <dd>{{$item->itemPrefix->str_mod * 100}}%</dd>
            <dt>Dex Modifier:</dt>
            <dd>{{$item->itemPrefix->dex_mod * 100}}%</dd>
            <dt>Dur Modifier:</dt>
            <dd>{{$item->itemPrefix->dur_mod * 100}}%</dd>
            <dt>Int Modifier:</dt>
            <dd>{{$item->itemPrefix->int_mod * 100}}%</dd>
            <dt>Chr Modifier:</dt>
            <dd>{{$item->itemPrefix->chr_mod * 100}}%</dd>
            <dt>Skill Name:</dt>
            <dd>{{is_null($item->itemPrefix->skill_name) ? 'N/A' : $item->itemPrefix->skill_name}}</dd>
            <dt>Skill Bonus:</dt>
            <dd>{{is_null($item->itemPrefix->skill_name) ? 0 : $item->itemPrefix->skill_bonus * 100}}%</dd>
        </dl>
        <hr/>
    @endif
    @if (!is_null($item->itemSuffix))
        <h6 class="mb-2">Suffix</h5>
        <p>{{$item->itemSuffix->description}}</p>
        <hr />
        <dl>
            <dt>Name:</dt>
            <dd>{{$item->itemSuffix->name}}</dd>
            <dt>Base Damage:</dt>
            <dd>{{$item->itemSuffix->base_damage_mod * 100}}%</dd>
            <dt>Base Healing Mod:</dt>
            <dd>{{$item->itemSuffix->base_healing_mod * 100}}%</dd>
            <dt>Str Modifier:</dt>
            <dd>{{$item->itemSuffix->str_mod * 100}}%</dd>
            <dt>Dex Modifier:</dt>
            <dd>{{$item->itemSuffix->dex_mod * 100}}%</dd>
            <dt>Dur Modifier:</dt>
            <dd>{{$item->itemSuffix->dur_mod * 100}}%</dd>
            <dt>Int Modifier:</dt>
            <dd>{{$item->itemSuffix->int_mod * 100}}%</dd>
            <dt>Chr Modifier:</dt>
            <dd>{{$item->itemSuffix->chr_mod * 100}}%</dd>
            <dt>Skill Name:</dt>
            <dd>{{is_null($item->itemSuffix->skill_name) ? 'N/A' : $item->itemSuffix->skill_name}}</dd>
            <dt>Skill Bonus:</dt>
            <dd>{{is_null($item->itemSuffix->skill_name) ? 0 : $item->itemSuffix->skill_bonus * 100}}%</dd>
        </dl>
    @endif
@endif