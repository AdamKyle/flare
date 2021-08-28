<div class="row">
    @if (!is_null($item->itemPrefix))
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-2">Prefix</h5>
                    <p>{{$item->itemPrefix->description}}</p>
                    <hr />
                    <dl>
                        <dt>Name:</dt>
                        <dd>{{$item->itemPrefix->name}}</dd>
                        <dt>Base Damage Modifier:</dt>
                        <dd class="{{$item->itemPrefix->base_damage_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->base_damage_mod * 100}}%</dd>
                        <dt>Base AC Modifier:</dt>
                        <dd class="{{$item->itemPrefix->base_ac_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->base_ac_mod * 100}}%</dd>
                        <dt>Base Healing Modifier:</dt>
                        <dd class="{{$item->itemPrefix->base_healing_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->base_healing_mod * 100}}%</dd>
                        <dt>Base Fight Timeout Modifier:</dt>
                        <dd class="{{$item->itemPrefix->fight_time_out_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->fight_time_out_mod_bonus * 100}}%</dd>
                        <dt>Str Modifier:</dt>
                        <dd class="{{$item->itemPrefix->str_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->str_mod * 100}}%</dd>
                        <dt>Dex Modifier:</dt>
                        <dd class="{{$item->itemPrefix->dex_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->dex_mod * 100}}%</dd>
                        <dt>Dur Modifier:</dt>
                        <dd class="{{$item->itemPrefix->dur_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->dur_mod * 100}}%</dd>
                        <dt>Int Modifier:</dt>
                        <dd class="{{$item->itemPrefix->int_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->int_mod * 100}}%</dd>
                        <dt>Chr Modifier:</dt>
                        <dd class="{{$item->itemPrefix->chr_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->chr_mod * 100}}%</dd>
                        <dt>Skill Name:</dt>
                        <dd>{{is_null($item->itemPrefix->skill_name) ? 'N/A' : $item->itemPrefix->skill_name}}</dd>
                        <dt>Skill XP Bonus (When Training):</dt>
                        <dd class="{{is_null($item->itemPrefix->skill_name) ? $item->itemPrefix->skill_training_bonus > 0.0 ? 'text-success' : '' : ''}}">{{is_null($item->itemPrefix->skill_name) ? 0 : $item->itemPrefix->skill_training_bonus * 100}}%</dd>
                        <dt>Skill Bonus (When using)</dt>
                        <dd class="{{is_null($item->itemPrefix->skill_name) ? $item->itemPrefix->skill_bonus > 0.0 ? 'text-success' : '' : ''}}">{{is_null($item->itemPrefix->skill_bonus) ? 0 : $item->itemPrefix->skill_bonus * 100}}%</dd>
                    </dl>
                </div>
            </div>
        </div>
    @endif
    @if (!is_null($item->itemSuffix))
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="mb-2">Suffix</h5>
                    <p>{{$item->itemSuffix->description}}</p>
                    <hr />
                    <dl>
                        <dt>Name:</dt>
                        <dd>{{$item->itemSuffix->name}}</dd>
                        <dt>Base Damage Modifier:</dt>
                        <dd class="{{$item->itemSuffix->base_damage_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_damage_mod * 100}}%</dd>
                        <dt>Base AC Modifier:</dt>
                        <dd class="{{$item->itemSuffix->base_ac_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_ac_mod * 100}}%</dd>
                        <dt>Base Healing Modifier:</dt>
                        <dd class="{{$item->itemSuffix->base_healing_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_healing_mod * 100}}%</dd>
                        <dt>Base Fight Timeout Modifier:</dt>
                        <dd class="{{$item->itemSuffix->fight_time_out_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->fight_time_out_mod_bonus * 100}}%</dd>
                        <dt>Str Modifier:</dt>
                        <dd class="{{$item->itemSuffix->str_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->str_mod * 100}}%</dd>
                        <dt>Dex Modifier:</dt>
                        <dd class="{{$item->itemSuffix->dex_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->dex_mod * 100}}%</dd>
                        <dt>Dur Modifier:</dt>
                        <dd class="{{$item->itemSuffix->dur_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->dur_mod * 100}}%</dd>
                        <dt>Int Modifier:</dt>
                        <dd class="{{$item->itemSuffix->int_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->int_mod * 100}}%</dd>
                        <dt>Chr Modifier:</dt>
                        <dd class="{{$item->itemSuffix->chr_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->chr_mod * 100}}%</dd>
                        <dt>Skill Name:</dt>
                        <dd>{{is_null($item->itemSuffix->skill_name) ? 'N/A' : $item->itemSuffix->skill_name}}</dd>
                        <dt>Skill XP Bonus (When Training):</dt>
                        <dd class="">{{is_null($item->itemSuffix->skill_name) ? 0 : $item->itemSuffix->skill_training_bonus * 100}}%</dd>
                        <dt>Skill Bonus (When using)</dt>
                        <dd>{{is_null($item->itemSuffix->skill_training_bonus) ? 0 : $item->itemSuffix->skill_bonus * 100}}%</dd>
                    </dl>
                </div>
            </div>
        </div>
    @endif
</div>

