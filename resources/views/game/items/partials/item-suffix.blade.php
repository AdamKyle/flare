@php
    $baseInfoId    = Str::random(10);
    $statsId       = Str::random(10);
    $skillsId      = Str::random(10);
    $damageId      = Str::random(10);
    $resistanceId  = Str::random(10);
@endphp

<x-tabs.pill-tabs-container>
  <x-tabs.tab tab="suffix-base-info-{{$baseInfoId}}" title="Base" selected="true" active="true" />
  <x-tabs.tab tab="suffix-stats-{{$statsId}}" title="Stats" selected="false" active="false" />
  <x-tabs.tab tab="suffix-skills-{{$skillsId}}" title="Skills" selected="false" active="false" />
  <x-tabs.tab tab="suffix-damage-{{$damageId}}" title="Damage" selected="false" active="false" />
  <x-tabs.tab tab="suffix-resistance-{{$resistanceId}}" title="Resiatance" selected="false" active="false" />
</x-tabs.pill-tabs-container>
<x-tabs.tab-content>
  <x-tabs.tab-content-section tab="suffix-base-info-{{$baseInfoId}}" active="true">
    <dl>
      <dt>Name:</dt>
      <dd>{{$item->itemSuffix->name}}</dd>
      <dt>Base Attack Modifier:</dt>
      <dd class="{{$item->itemSuffix->base_damage_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_damage_mod * 100}}%</dd>
      <dt>Skill Damage Modifier:</dt>
      <dd class="{{$item->itemSuffix->base_damage_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_damage_mod_bonus * 100}}%</dd>
      <dt>Base AC Modifier:</dt>
      <dd class="{{$item->itemSuffix->base_ac_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_ac_mod * 100}}%</dd>
      <dt>Skill AC Modifier:</dt>
      <dd class="{{$item->itemSuffix->base_ac_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_ac_mod_bonus * 100}}%</dd>
      <dt>Base Healing Modifier:</dt>
      <dd class="{{$item->itemSuffix->base_healing_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_healing_mod * 100}}%</dd>
      <dt>Skill Healing Modifier:</dt>
      <dd class="{{$item->itemSuffix->base_healing_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->base_healing_mod_bonus * 100}}%</dd>
      <dt>Class Bonus Mod:</dt>
      <dd class="{{$item->itemSuffix->class_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->class_bonus * 100}}%</dd>
      <dt>Base Fight Timeout Modifier:</dt>
      <dd class="{{$item->itemSuffix->fight_time_out_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->fight_time_out_mod_bonus * 100}}%</dd>
      <dt>Base Move Timeout Modifier:</dt>
      <dd class="{{$item->itemSuffix->move_time_out_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->move_time_out_mod_bonus * 100}}%</dd>
    </dl>
  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="suffix-stats-{{$statsId}}" active="false">
    <div class="row">
      <div class={{$item->itemSuffix->reduces_enemy_stats ? 'col-md-6' : 'col-md-12'}}>
        <dl>
          <dt><i class="fas fa-level-up-alt text-success"></i> Str Mod:</dt>
          <dd class="{{$item->itemSuffix->str_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->str_mod * 100}}%</dd>
          <dt><i class="fas fa-level-up-alt text-success"></i> Dex Mod:</dt>
          <dd class="{{$item->itemSuffix->dex_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->dex_mod * 100}}%</dd>
          <dt><i class="fas fa-level-up-alt text-success"></i> Dur Mod:</dt>
          <dd class="{{$item->itemSuffix->dur_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->dur_mod * 100}}%</dd>
          <dt><i class="fas fa-level-up-alt text-success"></i> Int Mod:</dt>
          <dd class="{{$item->itemSuffix->int_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->int_mod * 100}}%</dd>
          <dt><i class="fas fa-level-up-alt text-success"></i> Chr Mod:</dt>
          <dd class="{{$item->itemSuffix->chr_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->chr_mod * 100}}%</dd>
          <dt><i class="fas fa-level-up-alt text-success"></i> Agi Mod:</dt>
          <dd class="{{$item->itemSuffix->agi_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->agi_mod * 100}}%</dd>
          <dt><i class="fas fa-level-up-alt text-success"></i> Focus Mod:</dt>
          <dd class="{{$item->itemSuffix->focus_mod > 0.0 ? 'text-success' : ''}}">{{$item->itemSuffix->focus_mod * 100}}%</dd>
        </dl>
      </div>
      <div class={{$item->itemSuffix->reduces_enemy_stats ? 'col-md-6' : 'hide'}}>
        <dl>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Str Mod:</dt>
          <dd>{{$item->itemSuffix->str_reduction * 100}}%</dd>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Dex Mod:</dt>
          <dd>{{$item->itemSuffix->dex_reduction * 100}}%</dd>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Dur Mod:</dt>
          <dd>{{$item->itemSuffix->dur_reduction * 100}}%</dd>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Int Mod:</dt>
          <dd>{{$item->itemSuffix->int_reduction * 100}}%</dd>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Chr Mod:</dt>
          <dd>{{$item->itemSuffix->chr_reduction * 100}}%</dd>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Agi Mod:</dt>
          <dd>{{$item->itemSuffix->agi_reduction * 100}}%</dd>
          <dt><i class="fas fa-level-down-alt text-danger"></i> Focus Mod:</dt>
          <dd>{{$item->itemSuffix->focus_reduction * 100}}%</dd>
        </dl>
        <p class="mt-2 text-info">Affects enemies only.</p>
      </div>
    </div>
  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="suffix-skills-{{$skillsId}}" active="false">
    <div class="row">
      <div class="{{$item->itemSuffix->skill_reduction > 0 ? 'col-md-6' : 'col-md-12'}}">
        <dl>
          <dt>Skill Name:</dt>
          <dd>{{is_null($item->itemSuffix->skill_name) ? 'N/A' : $item->itemSuffix->skill_name}}</dd>
          <dt>Skill XP Bonus (When Training):</dt>
          <dd class="{{is_null($item->itemSuffix->skill_name) ? $item->itemSuffix->skill_training_bonus > 0.0 ? 'text-success' : '' : ''}}">{{is_null($item->itemSuffix->skill_name) ? 0 : $item->itemSuffix->skill_training_bonus * 100}}%</dd>
          <dt>Skill Bonus (When using)</dt>
          <dd class="{{is_null($item->itemSuffix->skill_name) ? $item->itemSuffix->skill_bonus > 0.0 ? 'text-success' : '' : ''}}">{{is_null($item->itemSuffix->skill_bonus) ? 0 : $item->itemSuffix->skill_bonus * 100}}%</dd>
        </dl>
      </div>

      <div class="{{$item->itemSuffix->skill_reduction > 0 ? 'col-md-6' : 'hide'}}">
        <dl>
          <dt>Skills Affected:</dt>
          <dd>Accuracy, Criticality, Casting Accuracy and Dodge</dd>
          <dt>Skill Reduction %:</dt>
          <dd class="{{$item->itemSuffix->skill_reduction > 0.0 ? 'text-danger' : ''}}">{{$item->itemSuffix->skill_reduction * 100}}%</dd>
        </dl>

        <p class="mt-2 text-info">Affects enemies only.</p>
      </div>
    </div>

  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="suffix-damage-{{$damageId}}" active="false">
    <dl>
      <dt>Damage:</dt>
      <dd class={{$item->itemSuffix->damage > 0 ? 'text-success' : ''}}>{{number_format($item->itemSuffix->damage)}}</dd>
      <dt>Is Damage Irresistible?:</dt>
      <dd>{{$item->itemSuffix->irresistible_damage ? 'Yes' : 'No'}}</dd>
      <dt>Can Stack:</dt>
      <dd>{{$item->itemSuffix->damage_can_stack ? 'Yes' : 'No'}}</dd>
      @if (!is_null($item->itemSuffix->steal_life_amount))
        <dt>Steal Life Amount:</dt>
        <dd class={{$item->itemSuffix->steal_life_amount > 0 ? 'text-success' : ''}}>{{$item->itemSuffix->steal_life_amount * 100}}%</dd>
      @endif

      @if ($item->itemSuffix->entranced_chance > 0.0)
        <dt>Entrance Chance:</dt>
        <dd class={{$item->itemSuffix->entranced_chance > 0 ? 'text-success' : ''}}>{{$item->itemSuffix->entranced_chance * 100}}%</dd>
      @endif
      @if ($item->itemSuffix->devouring_light > 0)
        <dt>Devouring Light (Voidance Chance):</dt>
        <dd class={{$item->itemSuffix->devouring_light > 0 ? 'text-success' : ''}}>{{$item->itemSuffix->devouring_light * 100}}%</dd>
      @endif
    </dl>
  </x-tabs.tab-content-section>
  <x-tabs.tab-content-section tab="suffix-resistance-{{$resistanceId}}" active="false">
    <dl>
      <dt>Resistance Reduction:</dt>
      <dd class="{{$item->itemSuffix->resiatance_reduction > 0.0 ? 'text-danger' : ''}}">{{$item->itemSuffix->resistance_reduction * 100}}%</dd>
      <p class="mt-2 text-info">Affects enemies only. Affects their: Spell Evasion, Artifact Annulment and Affix Resistance.</p>
    </dl>
  </x-tabs.tab-content-section>
</x-tabs.tab-content>