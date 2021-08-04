<dl>
    <dt>Damage:</dt>
    <dd>{{$details['slot']->item->getTotalDamage()}} <em>(With all modifiers)</em></dd>
    <dt>Position:</dt>
    <dd>{{title_case(str_replace('-', ' ', $details['slot']->position))}}</dd>
</dl>
<hr />

@if (!is_null($details['slot']->item->itemPrefix) || !is_null($details['slot']->item->itemSuffix))
    <div class="container">
        <h4>Attached Affixes</h4>

        @if (!is_null($details['slot']->item->itemPrefix))
            <hr />
            <div class="row">
                <div class="col-md-6">
                    <dl>
                        <dt>Name:</dt>
                        <dd>{{$details['slot']->item->itemPrefix->name}}</dd>
                        <dt>Base Damage:</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->base_damage_mod > 0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->base_damage_mod * 100}}%</dd>
                        <dt>Base AC:</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->base_ac_mod > 0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->base_ac_mod * 100}}%</dd>
                        <dt>Affects Skill:</dt>
                        @php $name = $details['slot']->item->itemPrefix->skill_name; @endphp
                        <dd>{{!is_null($name) ? $name : 'N/A'}}</dd>
                        <dt>XP Bonus (when using):</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->skill_training_bonus > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->skill_training_bonus * 100}}%</dd>
                        <dt>Bonus (when using):</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->skill_bonus > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->skill_bonus * 100}}%</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl>
                        <dt>Str mod:</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->str_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->str_mod * 100}}%</dd>
                        <dt>Dur mod:</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->dur_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->dur_mod * 100}}%</dd>
                        <dt>Dex mod:</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->dex_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->dex_mod * 100}}%</dd>
                        <dt>Chr mod:</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->chr_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->chr_mod * 100}}%</dd>
                        <dt>Int mod:</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->int_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->int_mod * 100}}%</dd>
                        <dt>Agi mod:</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->agi_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->agi_mod * 100}}%</dd>
                        <dt>Focus mod:</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->focus_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->focus_mod * 100}}%</dd>
                    </dl>
                </div>
            </div>
        @endif
        @if (!is_null($details['slot']->item->itemSuffix))
            <hr />
            <div class="row">
                <div class="col-md-6">
                    <dl>
                        <dt>Name:</dt>
                        <dd>{{$details['slot']->item->itemSuffix->name}}</dd>
                        <dt>Base Damage:</dt>
                        <dd class="{{$details['slot']->item->itemSuffix->base_damage_mod > 0 ? 'text-success' : ''}}">{{$details['slot']->item->itemSuffix->base_damage_mod * 100}}%</dd>
                        <dt>Base AC:</dt>
                        <dd class="{{$details['slot']->item->itemSuffix->base_ac_mod > 0 ? 'text-success' : ''}}">{{$details['slot']->item->itemSuffix->base_ac_mod * 100}}%</dd>
                        <dt>Affects Skill:</dt>
                        @php $name = $details['slot']->item->itemSuffix->skill_name; @endphp
                        <dd>{{!is_null($name) ? $name : 'N/A'}}</dd>
                        <dt>XP Bonus (when using):</dt>
                        <dd class="{{$details['slot']->item->itemSuffix->skill_training_bonus > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemSuffix->skill_training_bonus * 100}}%</dd>
                        <dt>Bonus (when using):</dt>
                        <dd class="{{$details['slot']->item->itemSuffix->skill_bonus > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemSuffix->skill_bonus * 100}}%</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <dl>
                        <dt>Str mod:</dt>
                        <dd class="{{$details['slot']->item->itemSuffix->str_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemSuffix->str_mod * 100}}%</dd>
                        <dt>Dur mod:</dt>
                        <dd class="{{$details['slot']->item->itemSuffix->dur_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemSuffix->dur_mod * 100}}%</dd>
                        <dt>Dex mod:</dt>
                        <dd class="{{$details['slot']->item->itemSuffix->dex_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemSuffix->dex_mod * 100}}%</dd>
                        <dt>Chr mod:</dt>
                        <dd class="{{$details['slot']->item->itemSuffix->chr_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemSuffix->chr_mod * 100}}%</dd>
                        <dt>Int mod:</dt>
                        <dd class="{{$details['slot']->item->itemSuffix->int_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemSuffix->int_mod * 100}}%</dd>
                        <dt>Agi mod:</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->agi_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->agi_mod * 100}}%</dd>
                        <dt>Focus mod:</dt>
                        <dd class="{{$details['slot']->item->itemPrefix->focus_mod > 0.0 ? 'text-success' : ''}}">{{$details['slot']->item->itemPrefix->focus_mod * 100}}%</dd>
                    </dl>
                </div>
            </div>
        @endif
    </div>
    <hr />
@endif

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
                    <dd class="{{$skill['skill_training_bonus'] > 0.0 ? 'text-success' : ''}}">{{$skill['skill_training_bonus'] * 100}}%</dd>
                    <dt>Skill Bonus (When using)</dt>
                    <dd class="{{$skill['skill_bonus'] > 0.0 ? 'text-success' : ''}}">{{$skill['skill_bonus'] * 100}}%</dd>
                </dl>
            </div>
        @endforeach
    </div>
    <hr />
@endif
