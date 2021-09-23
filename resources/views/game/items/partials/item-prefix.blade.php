<x-tabs.pill-tabs-container>
  <x-tabs.tab tab="prefix-base-info" title="Base Information" selected="true" active="true" />
  <x-tabs.tab tab="prefix-stats" title="Stats" selected="false" active="false" />
  <x-tabs.tab tab="prefix-skills" title="Skills" selected="false" active="false" />
  <x-tabs.tab tab="prefix-damage" title="Damage" selected="false" active="false" />
</x-tabs.pill-tabs-container>
<x-tabs.tab-content>
  <x-tabs.tab-content-section tab="prefix-base-info" active="true">
    <dl>
      <dt>Name:</dt>
      <dd>{{$item->itemPrefix->name}}</dd>
      <dt>Base Attack Modifier:</dt>
      <dd class="{{$item->itemPrefix->base_damage_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->base_damage_mod * 100}}%</dd>
      <dt>Base Damage Modifier (affects skills):</dt>
      <dd class="{{$item->itemPrefix->base_damage_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->base_damage_mod_bonus * 100}}%</dd>
      <dt>Base AC Modifier:</dt>
      <dd class="{{$item->itemPrefix->base_ac_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->base_ac_mod * 100}}%</dd>
      <dt>Base Healing Modifier:</dt>
      <dd class="{{$item->itemPrefix->base_healing_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->base_healing_mod * 100}}%</dd>
      <dt>Class Bonus Mod:</dt>
      <dd class="{{$item->itemPrefix->class_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->class_bonus * 100}}%</dd>
      <dt>Base Fight Timeout Modifier:</dt>
      <dd class="{{$item->itemPrefix->fight_time_out_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->fight_time_out_mod_bonus * 100}}%</dd>
    </dl>
  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="prefix-stats" active="false">
    <dl>
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
      <dt>Agi Modifier:</dt>
      <dd class="{{$item->itemPrefix->agi_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->agi_mod * 100}}%</dd>
      <dt>Focus Modifier:</dt>
      <dd class="{{$item->itemPrefix->focus_nod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->focus_nod * 100}}%</dd>
    </dl>
  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="prefix-skills" active="false">
    <dl>
      <dt>Skill Name:</dt>
      <dd>{{is_null($item->itemPrefix->skill_name) ? 'N/A' : $item->itemPrefix->skill_name}}</dd>
      <dt>Skill XP Bonus (When Training):</dt>
      <dd class="{{is_null($item->itemPrefix->skill_name) ? $item->itemPrefix->skill_training_bonus > 0.0 ? 'text-success' : '' : ''}}">{{is_null($item->itemPrefix->skill_name) ? 0 : $item->itemPrefix->skill_training_bonus * 100}}%</dd>
      <dt>Skill Bonus (When using)</dt>
      <dd class="{{is_null($item->itemPrefix->skill_name) ? $item->itemPrefix->skill_bonus > 0.0 ? 'text-success' : '' : ''}}">{{is_null($item->itemPrefix->skill_bonus) ? 0 : $item->itemPrefix->skill_bonus * 100}}%</dd>
    </dl>
  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="prefix-damage" active="false">
    <dl>
      <dt>Damage:</dt>
      <dd>{{$item->itemPrefix->damage}}</dd>
      <dt>Is Damage Irresistible?:</dt>
      <dd>{{$item->itemPrefix->irresistible_damage ? 'Yes' : 'No'}}</dd>
      <dt>Can Stack:</dt>
      <dd>{{$item->itemPrefix->damage_can_stack ? 'Yes' : 'No'}}</dd>
    </dl>

    @if (!is_null($item->itemPrefix->steal_life_amount))
      <dl>
        <dt>Steal Life Amount:</dt>
        <dd class={{$item->itemPrefix->steal_life_amount > 0 ? 'text-success' : ''}}>{{$item->itemPrefix->steal_life_amount * 100}}%</dd>
      </dl>
    @endif
    @if ($item->itemPrefix->entranced_chance > 0)
      <dt>Entrance Chance:</dt>
      <dd class={{$item->itemPrefix->entranced_chance > 0 ? 'text-success' : ''}}>{{$item->itemPrefix->entranced_chance * 100}}%</dd>
    @endif
  </x-tabs.tab-content-section>
</x-tabs.tab-content>