@if (!is_null($item->itemPrefix) || !is_null($item->itemSuffix))
    <div class="container">
        <h4 class="text-center">Attached Affixes</h4>

        <div class="row">
            <div class={{!is_null($item->itemSuffix) ? 'col-md-6' : 'col-md-12'}}>
                @if (!is_null($item->itemPrefix))
                    <hr />
                    @include('game.items.partials.item-prefix', ['item' => $item])
                @endif
            </div>
            <div class={{!is_null($item->itemPrefix) ? 'col-md-6' : 'col-md-12'}}>
                @if (!is_null($item->itemSuffix))
                    <hr />
                    @include('game.items.partials.item-suffix', ['item' => $item])
                @endif
            </div>
        </div>
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
<h4>Stat Details:</h4>
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
