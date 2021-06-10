@if (!is_null($item->itemPrefix) || !is_null($item->itemSuffix))
    <div class="container">
        <h4>Attached Affixes</h4>

        @if (!is_null($item->itemPrefix))
            <hr />
            <div class="row">
                <div class="col-md-6">
                    <dl>
                        <dt>Name:</dt>
                        <dd>{{$item->itemPrefix->name}}</dd>
                        <dt>Base Damage:</dt>
                        <dd>{{$item->itemPrefix->base_damage_mod * 100}}%</dd>
                        <dt>Base AC:</dt>
                        <dd>{{$item->itemPrefix->base_ac_mod * 100}}%</dd>
                        <dt>Affects Skill:</dt>
                        @php $name = $item->itemPrefix->skill_name; @endphp
                        <dd>{{!is_null($name) ? $name : 'N/A'}}</dd>
                        <dt>XP Bonus (when using):</dt>
                        <dd>{{$item->itemPrefix->skill_training_bonus * 100}}%</dd>
                        <dt>Bonus (when using):</dt>
                        <dd>{{$item->itemPrefix->skill_bonus * 100}}%</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl>
                        <dt>Str mod:</dt>
                        <dd>{{$item->itemPrefix->str_mod * 100}}%</dd>
                        <dt>Dur mod:</dt>
                        <dd>{{$item->itemPrefix->dur_mod * 100}}%</dd>
                        <dt>Dex mod:</dt>
                        <dd>{{$item->itemPrefix->dex_mod * 100}}%</dd>
                        <dt>Chr mod:</dt>
                        <dd>{{$item->itemPrefix->chr_mod * 100}}%</dd>
                        <dt>Int mod:</dt>
                        <dd>{{$item->itemPrefix->int_mod * 100}}%</dd>
                    </dl>
                </div>
            </div>
        @endif
        @if (!is_null($item->itemSuffix))
            <hr />
            <div class="row">
                <div class="col-md-6">
                    <dl>
                        <dt>Name:</dt>
                        <dd>{{$item->itemSuffix->name}}</dd>
                        <dt>Base Damage:</dt>
                        <dd>{{$item->itemSuffix->base_damage_mod * 100}}%</dd>
                        <dt>Base AC:</dt>
                        <dd>{{$item->itemSuffix->base_ac_mod * 100}}%</dd>
                        <dt>Affects Skill:</dt>
                        @php $name = $item->itemSuffix->skill_name; @endphp
                        <dd>{{!is_null($name) ? $name : 'N/A'}}</dd>
                        <dt>XP Bonus (when using):</dt>
                        <dd>{{$item->itemSuffix->skill_training_bonus * 100}}%</dd>
                        <dt>Bonus (when using):</dt>
                        <dd>{{$item->itemSuffix->skill_bonus * 100}}%</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl>
                        <dt>Str mod:</dt>
                        <dd>{{$item->itemSuffix->str_mod * 100}}%</dd>
                        <dt>Dur mod:</dt>
                        <dd>{{$item->itemSuffix->dur_mod * 100}}%</dd>
                        <dt>Dex mod:</dt>
                        <dd>{{$item->itemSuffix->dex_mod * 100}}%</dd>
                        <dt>Chr mod:</dt>
                        <dd>{{$item->itemSuffix->chr_mod * 100}}%</dd>
                        <dt>Int mod:</dt>
                        <dd>{{$item->itemSuffix->int_mod * 100}}%</dd>
                    </dl>
                </div>
            </div>
        @endif
    </div>
    <hr />
@endif
@if (!is_null($item->skill_name))
    <div class="container">
        <h4>Affects Skills</h4>
        <hr />
        <div class="row">
            <div class="col-sm-12">
                <dl>
                    <dt>Skill Name:</dt>
                    <dd>{{$item->skill_name}}</dd>
                    <dt>Skill XP Bonus (When Training):</dt>
                    <dd>{{$item->skill_training_bonus * 100}}%</dd>
                    <dt>Skill Bonus (When using)</dt>
                    <dd>{{$item->skill_bonus * 100}}%</dd>
                </dl>
            </div>
        </div>
    </div>
    <hr />
@endif
<h6>Stat Details:</h6>
<p>These stat increases so <span class="text-success">green for any increase</span> and <span class="text-danger"> red for any decrease</span></p>
@if (empty($details))
    @include('game.character.partials.equipment.sections.equip.details.item-stat-details', ['item' => $item])
@else
    @if (!is_null($item->default_position))
        @include('game.character.partials.equipment.sections.equip.details.stat-details', ['details' => $details, 'hasDefaultPosition' => true])
    @else
        <div class="row">
            @include('game.character.partials.equipment.sections.equip.details.stat-details', ['details' => $details, 'hasDefaultPosition' => false])

            @if (count(array_keys($details)) < 2 && ($item->crafting_type !== 'armour' || $item->type === 'shield'))
                <div class="col-md-6 mt-4">
                    <p>If Equipped As Second Item:</p>
                    @include('game.character.partials.equipment.sections.equip.details.item-stat-details', ['item' => $item])
                </div>
            @endif
        </div>
    @endif

@endif
<hr />
