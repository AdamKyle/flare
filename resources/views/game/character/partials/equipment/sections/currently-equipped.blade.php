<dl>
    <dt>Damage:</dt>
    <dd>{{$details['slot']->item->getTotalDamage()}} <em>(With all modifiers)</em></dd>
    <dt>Position:</dt>
    <dd>{{title_case(str_replace('-', ' ', $details['slot']->position))}}</dd>
</dl>
<hr />

@if (is_null($details['slot']->item->itemPrefix) && is_null($details['slot']->item->itemSuffix))
    <div class="alert alert-info">
        There are no affixes on this item.
    </div>
@else
    @if (!is_null($details['slot']->item->itemPrefix))
        <dl>
            <dt>Name:</dt>
            <dd>{{$details['slot']->item->itemPrefix->name}}</dd>
            <dt>Base Damage:</dt>
            <dd>{{$details['slot']->item->itemPrefix->base_damage_mod * 100}}%</dd>
            <dt>Base AC:</dt>
            <dd>{{$details['slot']->item->itemPrefix->base_ac_mod * 100}}%</dd>
            <dt>Description:</dt>
            <dd>{{$details['slot']->item->itemPrefix->description}}</dd>
        </dl>
    @endif
    @if (!is_null($details['slot']->item->itemSuffix))
        <dl>
            <dt>Name:</dt>
            <dd>{{$details['slot']->item->itemSuffix->name}}</dd>
            <dt>Base Damage:</dt>
            <dd>{{$details['slot']->item->itemSuffix->base_damage_mod * 100}}%</dd>
            <dt>Base AC:</dt>
            <dd>{{$details['slot']->item->itemSuffix->base_ac_mod * 100}}%</dd>
            <dt>Description:</dt>
            <dd>{{$details['slot']->item->itemSuffix->description}}</dd>
        </dl>
    @endif
@endif
<hr />
@if (!empty($details['slot']->item->getItemSkills()))
    <h4 class="mt-3">Affects the Following Skills:</h4>
    <hr />
    <div class="row mt-3">
        @php
            $col = (12 / count($details['slot']->item->getItemSkills()));
        @endphp

        @foreach($details['slot']->item->getItemSkills() as $skill)
            <div class="col-md-{{$col}}">
                <dl>
                    <dt>Skill Name:</dt>
                    <dd>{{$skill['skill_name']}}</dd>
                    <dt>Skill XP Bonus (When Training):</dt>
                    <dd>{{$skill['skill_training_bonus'] * 100}}%</dd>
                    <dt>Skill Bonus (When using)</dt>
                    <dd>{{$skill['skill_bonus'] * 100}}%</dd>
                </dl>
            </div>
        @endforeach
    </div>
@endif
<hr />
