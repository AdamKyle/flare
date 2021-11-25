@php
  $baseInfoId     = Str::random(10);
  $statsId        = Str::random(10);
  $skillsId       = Str::random(10);
  $damageId       = Str::random(10);
  $resiatnceId    = Str::random(10);
@endphp

<x-tabs.pill-tabs-container>
  <x-tabs.tab tab="prefix-base-info-{{$baseInfoId}}" title="Base" selected="true" active="true" />
  <x-tabs.tab tab="prefix-stats-{{$statsId}}" title="Stats" selected="false" active="false" />
  <x-tabs.tab tab="prefix-skills-{{$skillsId}}" title="Skills" selected="false" active="false" />
  <x-tabs.tab tab="prefix-damage-{{$damageId}}" title="Damage" selected="false" active="false" />
  <x-tabs.tab tab="prefix-resistance-{{$resiatnceId}}" title="Resiatance" selected="false" active="false" />
</x-tabs.pill-tabs-container>
<x-tabs.tab-content>
  <x-tabs.tab-content-section tab="prefix-base-info-{{$baseInfoId}}" active="true">
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
  <x-tabs.tab-content-section tab="prefix-stats-{{$statsId}}" active="false">
    <div class="row">
      <div class={{$item->itemPrefix->reduces_enemy_stats ? 'col-md-6' : 'col-md-12'}}>
        <dl>
          <dt><i class="fas fa-level-up-alt text-success"></i> Str Mod:</dt>
          <dd class="{{$item->itemPrefix->str_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->str_mod * 100}}%</dd>
          <dt><i class="fas fa-level-up-alt text-success"></i> Dex Mod:</dt>
          <dd class="{{$item->itemPrefix->dex_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->dex_mod * 100}}%</dd>
          <dt><i class="fas fa-level-up-alt text-success"></i> Dur Mod:</dt>
          <dd class="{{$item->itemPrefix->dur_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->dur_mod * 100}}%</dd>
          <dt><i class="fas fa-level-up-alt text-success"></i> Int Mod:</dt>
          <dd class="{{$item->itemPrefix->int_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->int_mod * 100}}%</dd>
          <dt><i class="fas fa-level-up-alt text-success"></i> Chr Mod:</dt>
          <dd class="{{$item->itemPrefix->chr_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->chr_mod * 100}}%</dd>
          <dt><i class="fas fa-level-up-alt text-success"></i> Agi Mod:</dt>
          <dd class="{{$item->itemPrefix->agi_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->agi_mod * 100}}%</dd>
          <dt><i class="fas fa-level-up-alt text-success"></i> Focus Mod:</dt>
          <dd class="{{$item->itemPrefix->focus_nod > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->focus_nod * 100}}%</dd>
        </dl>
      </div>
      <div class={{$item->itemPrefix->reduces_enemy_stats ? 'col-md-6' : 'hide'}}>
        <dl>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Str Mod:</dt>
          <dd>{{$item->itemPrefix->str_reduction * 100}}%</dd>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Dex Mod:</dt>
          <dd>{{$item->itemPrefix->dex_reduction * 100}}%</dd>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Dur Mod:</dt>
          <dd>{{$item->itemPrefix->dur_reduction * 100}}%</dd>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Int Mod:</dt>
          <dd>{{$item->itemPrefix->int_reduction * 100}}%</dd>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Chr Mod:</dt>
          <dd>{{$item->itemPrefix->chr_reduction * 100}}%</dd>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Agi Mod:</dt>
          <dd>{{$item->itemPrefix->agi_reduction * 100}}%</dd>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Focus Mod:</dt>
          <dd>{{$item->itemPrefix->focus_reduction * 100}}%</dd>
        </dl>
        <p class="mt-2 text-info">Affects enemies only.</p>
      </div>
    </div>
  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="prefix-skills-{{$skillsId}}" active="false">
    <div class="row">
      <div class="{{$item->itemPrefix->skill_reduction > 0 ? 'col-md-6' : 'col-md-12'}}">
        <dl>
          <dt>Skill Name:</dt>
          <dd>{{is_null($item->itemPrefix->skill_name) ? 'N/A' : $item->itemPrefix->skill_name}}</dd>
          <dt>Skill XP Bonus (When Training):</dt>
          <dd class="{{is_null($item->itemPrefix->skill_name) ? $item->itemPrefix->skill_training_bonus > 0.0 ? 'text-success' : '' : ''}}">{{is_null($item->itemPrefix->skill_name) ? 0 : $item->itemPrefix->skill_training_bonus * 100}}%</dd>
          <dt>Skill Bonus (When using)</dt>
          <dd class="{{is_null($item->itemPrefix->skill_name) ? $item->itemPrefix->skill_bonus > 0.0 ? 'text-success' : '' : ''}}">{{is_null($item->itemPrefix->skill_bonus) ? 0 : $item->itemPrefix->skill_bonus * 100}}%</dd>
        </dl>
      </div>

      <div class="{{$item->itemPrefix->skill_reduction > 0 ? 'col-md-6' : 'hide'}}">
        <dl>
          <dt>Skills Affected:</dt>
          <dd>Accuracy, Criticality, Casting Accuracy and Dodge</dd>
          <dt>Skill Reduction %:</dt>
          <dd class="{{$item->itemPrefix->skill_reduction > 0.0 ? 'text-success' : ''}}">{{$item->itemPrefix->skill_reduction * 100}}%</dd>
        </dl>
      </div>
    </div>

  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="prefix-damage-{{$damageId}}" active="false">
    <dl>
      <dt>Damage:</dt>
      <dd class="{{$item->itemPrefix->damage > 0 ? 'text-success' : ''}}">{{number_format($item->itemPrefix->damage)}}</dd>
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
      <dl>
        <dt>Entrance Chance:</dt>
        <dd class={{$item->itemPrefix->entranced_chance > 0 ? 'text-success' : ''}}>{{$item->itemPrefix->entranced_chance * 100}}%</dd>
      </dl>
    @endif
    @if ($item->itemPrefix->devouring_light > 0)
      <dl>
        <dt>Devouring Light (Voidance Chance):</dt>
        <dd class={{$item->itemPrefix->devouring_light > 0 ? 'text-success' : ''}}>{{$item->itemPrefix->devouring_light * 100}}%</dd>
      </dl>
    @endif
  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="prefix-resistance-{{$resiatnceId}}" active="false">
    <dl>
      <dt>Resistance Reduction:</dt>
      <dd class="{{$item->itemPrefix->resiatance_reduction > 0.0 ? 'text-danger' : ''}}">{{$item->itemPrefix->resistance_reduction * 100}}%</dd>
      <p class="mt-2 text-info">Affects enemies only. Affects their: Spell Evasion, Artifact Annulment and Affix Resistance.</p>
    </dl>
  </x-tabs.tab-content-section>
</x-tabs.tab-content>