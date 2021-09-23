<x-tabs.pill-tabs-container>
  <x-tabs.tab tab="suffix-base-info" title="Base Information" selected="true" active="true" />
  <x-tabs.tab tab="suffix-stats" title="Stats" selected="false" active="false" />
  <x-tabs.tab tab="suffix-skills" title="Skills" selected="false" active="false" />
  <x-tabs.tab tab="suffix-damage" title="Damage" selected="false" active="false" />
</x-tabs.pill-tabs-container>
<x-tabs.tab-content>
  <x-tabs.tab-content-section tab="suffix-base-info" active="true">
    <dl>
      <dt>Name:</dt>
      <dd>{{$item->itemSuffix->name}}</dd>
      <dt>Base Attack Modifier:</dt>
      <dd class="{{$item->itemSuffix->base_damage_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_damage_mod * 100}}%</dd>
      <dt>Base Damage Modifier (affects skills):</dt>
      <dd class="{{$item->itemSuffix->base_damage_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_damage_mod_bonus * 100}}%</dd>
      <dt>Base AC Modifier:</dt>
      <dd class="{{$item->itemSuffix->base_ac_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_ac_mod * 100}}%</dd>
      <dt>Base Healing Modifier:</dt>
      <dd class="{{$item->itemSuffix->base_healing_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_healing_mod * 100}}%</dd>
      <dt>Class Bonus Mod:</dt>
      <dd class="{{$item->itemSuffix->class_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->class_bonus * 100}}%</dd>
      <dt>Base Fight Timeout Modifier:</dt>
      <dd class="{{$item->itemSuffix->fight_time_out_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->fight_time_out_mod_bonus * 100}}%</dd>
    </dl>
  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="suffix-stats" active="false">
    <dl>
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
      <dt>Agi Modifier:</dt>
      <dd class="{{$item->itemSuffix->agi_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->agi_mod * 100}}%</dd>
      <dt>Focus Modifier:</dt>
      <dd class="{{$item->itemSuffix->focus_nod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->focus_nod * 100}}%</dd>
    </dl>
  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="suffix-skills" active="false">
    <dl>
      <dt>Skill Name:</dt>
      <dd>{{is_null($item->itemSuffix->skill_name) ? 'N/A' : $item->itemSuffix->skill_name}}</dd>
      <dt>Skill XP Bonus (When Training):</dt>
      <dd class="{{is_null($item->itemSuffix->skill_name) ? $item->itemSuffix->skill_training_bonus > 0.0 ? 'text-success' : '' : ''}}">{{is_null($item->itemSuffix->skill_name) ? 0 : $item->itemSuffix->skill_training_bonus * 100}}%</dd>
      <dt>Skill Bonus (When using)</dt>
      <dd class="{{is_null($item->itemSuffix->skill_name) ? $item->itemSuffix->skill_bonus > 0.0 ? 'text-success' : '' : ''}}">{{is_null($item->itemSuffix->skill_bonus) ? 0 : $item->itemSuffix->skill_bonus * 100}}%</dd>
    </dl>
  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="suffix-damage" active="false">
    <dl>
      <dt>Damage:</dt>
      <dd class={{$item->itemSuffix->damage > 0 ? 'text-success' : ''}}>{{$item->itemSuffix->damage}}</dd>
      <dt>Is Damage Irresistible?:</dt>
      <dd>{{$item->itemSuffix->irresistible_damage ? 'Yes' : 'No'}}</dd>
      <dt>Can Stack:</dt>
      <dd>{{$item->itemSuffix->damage_can_stack ? 'Yes' : 'No'}}</dd>
      @if (!is_null($item->itemSuffix->steal_life_amount))
        <dt>Steal Life Amount:</dt>
        <dd class={{$item->itemSuffix->steal_life_amount > 0 ? 'text-success' : ''}}>{{$item->itemSuffix->steal_life_amount * 100}}%</dd>
      @endif
    </dl>
  </x-tabs.tab-content-section>
</x-tabs.tab-content>