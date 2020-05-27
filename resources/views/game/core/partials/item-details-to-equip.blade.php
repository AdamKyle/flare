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
    <dl>
        <dt>Attack:</dt>
        <dd><span class='text-success'>{{$item->getTotalDamage()}}</span></dd>
        <dt>AC:</dt>
        <dd><span class='text-success'>{{$item->getTotalDefence()}}</span></dd>
        <dt>Healing:</dt>
        <dd><span class='text-success'>{{$item->getTotalHealing()}}</span></dd>
        <dt>Str:</dt>
        <dd><span class='text-success'>{{$item->getTotalPercentageForStat('str') * 100}}%</span></dd>
        <dt>Dur:</dt>
        <dd><span class='text-success'>{{$item->getTotalPercentageForStat('dur') * 100}}%</span></dd>
        <dt>Dex:</dt>
        <dd><span class='text-success'>{{$item->getTotalPercentageForStat('dex') * 100}}%</span></dd>
        <dt>Chr:</dt>
        <dd><span class='text-success'>{{$item->getTotalPercentageForStat('chr') * 100}}%</span></dd>
        <dt>Int:</dt>
        <dd><span class='text-success'>{{$item->getTotalPercentageForStat('int') * 100}}%</span></dd>
    </dl>
@else
    @if (!is_null($item->default_position))
        @foreach($details as $key => $value)
            <p>If Replaced:</p>
            <dl>
                <dt>Attack:</dt>
                <dd><span class={{$value['damage_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['damage_adjustment']}}</span></dd>
                <dt>AC:</dt>
                <dd><span class={{$value['ac_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['ac_adjustment']}}</span></dd>
                <dt>Healing:</dt>
                <dd><span class={{$value['healing_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['healing_adjustment']}}</span></dd>
                <dt>Str:</dt>
                <dd><span class={{$value['str_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['str_adjustment'] * 100}}%</span></dd>
                <dt>Dur:</dt>
                <dd><span class={{$value['dur_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['dur_adjustment'] * 100}}%</span></dd>
                <dt>Dex:</dt>
                <dd><span class={{$value['dex_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['dex_adjustment'] * 100}}%</span></dd>
                <dt>Chr:</dt>
                <dd><span class={{$value['chr_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['chr_adjustment'] * 100}}%</span></dd>
                <dt>Int:</dt>
                <dd><span class={{$value['int_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['int_adjustment'] * 100}}%</span></dd>
            </dl>
        @endforeach
    @else
        <div class="row">
            <div class="col-md-6">
                @foreach($details as $key => $value)
                    <p>If Replaced:</p>
                    <dl>
                        <dt>Attack:</dt>
                        <dd><span class={{$value['damage_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['damage_adjustment']}}</span></dd>
                        <dt>AC:</dt>
                        <dd><span class={{$value['ac_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['ac_adjustment']}}</span></dd>
                        <dt>Healing:</dt>
                        <dd><span class={{$value['healing_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['healing_adjustment']}}</span></dd>
                        <dt>Str:</dt>
                        <dd><span class={{$value['str_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['str_adjustment'] * 100}}%</span></dd>
                        <dt>Dur:</dt>
                        <dd><span class={{$value['dur_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['dur_adjustment'] * 100}}%</span></dd>
                        <dt>Dex:</dt>
                        <dd><span class={{$value['dex_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['dex_adjustment'] * 100}}%</span></dd>
                        <dt>Chr:</dt>
                        <dd><span class={{$value['chr_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['chr_adjustment'] * 100}}%</span></dd>
                        <dt>Int:</dt>
                        <dd><span class={{$value['int_adjustment'] >= 0 ? 'text-success' : 'text-danger'}}>{{$value['int_adjustment'] * 100}}%</span></dd>
                    </dl>
                @endforeach
            </div>

            <div class="col-md-6">
                <p><strong>If equipped as second item:</strong></p>
                <dl>
                    <dt>Attack:</dt>
                    <dd><span class='text-success'>{{$item->getTotalDamage()}}</span></dd>
                    <dt>AC:</dt>
                    <dd><span class='text-success'>{{$item->getTotalDefence()}}</span></dd>
                    <dt>Healing:</dt>
                    <dd><span class='text-success'>{{$item->getTotalHealing()}}</span></dd>
                    <dt>Str:</dt>
                    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('str') * 100}}%</span></dd>
                    <dt>Dur:</dt>
                    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('dur') * 100}}%</span></dd>
                    <dt>Dex:</dt>
                    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('dex') * 100}}%</span></dd>
                    <dt>Chr:</dt>
                    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('chr') * 100}}%</span></dd>
                    <dt>Int:</dt>
                    <dd><span class='text-success'>{{$item->getTotalPercentageForStat('int') * 100}}%</span></dd>
                </dl>
            </div>
        </div>
    @endif
    
@endif
<hr />
