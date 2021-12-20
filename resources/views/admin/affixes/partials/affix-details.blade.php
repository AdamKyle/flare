<div class="row">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <p>{{$itemAffix->description}}</p>
        <hr />
        <x-tabs.pill-tabs-container>
          <x-tabs.tab tab="prefix-base-info" title="Base Information" selected="true" active="true" />
          <x-tabs.tab tab="prefix-stats" title="Stats" selected="false" active="false" />
          <x-tabs.tab tab="prefix-skills" title="Skills" selected="false" active="false" />
        </x-tabs.pill-tabs-container>
        <x-tabs.tab-content>
          <x-tabs.tab-content-section tab="prefix-base-info" active="true">
            <dl>
              <dt>Name:</dt>
              <dd>{{$itemAffix->name}}</dd>
              <dt>Base Attack Modifier:</dt>
              <dd class="{{$itemAffix->base_damage_mod > 0.0 ? 'text-success' : ''}}">{{$itemAffix->base_damage_mod * 100}}%</dd>
              <dt>Base Damage Modifier (affects skills):</dt>
              <dd class="{{$itemAffix->base_damage_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$itemAffix->base_damage_mod_bonus * 100}}%</dd>
              <dt>Base AC Modifier:</dt>
              <dd class="{{$itemAffix->base_ac_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$itemAffix->base_ac_mod_bonus * 100}}%</dd>
              <dt>Base Healing Modifier:</dt>
              <dd class="{{$itemAffix->base_healing_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$itemAffix->base_healing_mod_bonus * 100}}%</dd>
              <dt>Class Bonus Mod:</dt>
              <dd class="{{$itemAffix->class_bonus > 0.0 ? 'text-success' : ''}}">{{$itemAffix->class_bonus * 100}}%</dd>
              <dt>Base Fight Timeout Modifier:</dt>
              <dd class="{{$itemAffix->fight_time_out_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$itemAffix->fight_time_out_mod_bonus * 100}}%</dd>
              <dt>Base Move Timeout Modifier:</dt>
              <dd class="{{$itemAffix->move_time_out_mod_bonus > 0.0 ? 'text-success' : ''}}">{{$itemAffix->move_time_out_mod_bonus * 100}}%</dd>
            </dl>
          </x-tabs.tab-content-section>
          <x-tabs.tab-content-section tab="prefix-stats" active="false">
            <dl>
              <dt>Str Modifier:</dt>
              <dd class="{{$itemAffix->str_mod > 0.0 ? 'text-success' : ''}}">{{$itemAffix->str_mod * 100}}%</dd>
              <dt>Dex Modifier:</dt>
              <dd class="{{$itemAffix->dex_mod > 0.0 ? 'text-success' : ''}}">{{$itemAffix->dex_mod * 100}}%</dd>
              <dt>Dur Modifier:</dt>
              <dd class="{{$itemAffix->dur_mod > 0.0 ? 'text-success' : ''}}">{{$itemAffix->dur_mod * 100}}%</dd>
              <dt>Int Modifier:</dt>
              <dd class="{{$itemAffix->int_mod > 0.0 ? 'text-success' : ''}}">{{$itemAffix->int_mod * 100}}%</dd>
              <dt>Chr Modifier:</dt>
              <dd class="{{$itemAffix->chr_mod > 0.0 ? 'text-success' : ''}}">{{$itemAffix->chr_mod * 100}}%</dd>
              <dt>Agi Modifier:</dt>
              <dd class="{{$itemAffix->agi_mod > 0.0 ? 'text-success' : ''}}">{{$itemAffix->agi_mod * 100}}%</dd>
              <dt>Focus Modifier:</dt>
              <dd class="{{$itemAffix->focus_mod > 0.0 ? 'text-success' : ''}}">{{$itemAffix->focus_mod * 100}}%</dd>
            </dl>
          </x-tabs.tab-content-section>
          <x-tabs.tab-content-section tab="prefix-skills" active="false">
            <dl>
              <dt>Skill Name:</dt>
              <dd>{{is_null($itemAffix->skill_name) ? 'N/A' : $itemAffix->skill_name}}</dd>
              <dt>Skill XP Bonus (When Training):</dt>
              <dd class="{{is_null($itemAffix->skill_name) ? $itemAffix->skill_training_bonus > 0.0 ? 'text-success' : '' : ''}}">{{is_null($itemAffix->skill_name) ? 0 : $itemAffix->skill_training_bonus * 100}}%</dd>
              <dt>Skill Bonus (When using)</dt>
              <dd class="{{is_null($itemAffix->skill_name) ? $itemAffix->skill_bonus > 0.0 ? 'text-success' : '' : ''}}">{{is_null($itemAffix->skill_bonus) ? 0 : $itemAffix->skill_bonus * 100}}%</dd>
            </dl>
          </x-tabs.tab-content-section>
        </x-tabs.tab-content>
      </div>
    </div>

  </div>
  <div class="col-md-6">
    <h2 class="mt-2 mb-2">Enchanting Information</h2>

    <div class="card">
      <div class="card-body">
        <dl>
          <dt>Base Cost:</dt>
          <dd>{{number_format($itemAffix->cost)}} gold</dd>
          <dt>Intelligence Required:</dt>
          <dd>{{number_format($itemAffix->int_required)}}</dd>
          <dt>Level Required:</dt>
          <dd>{{$itemAffix->skill_level_required}}</dd>
          <dt>Level Becomes To Easy:</dt>
          <dd>{{$itemAffix->skill_level_trivial}}</dd>
        </dl>
      </div>
    </div>

    @if ($itemAffix->damage !== 0)
      <h2 class="mt-2 mb-2">Damage Information</h2>

      <div class="card">
        <div class="card-body">
          <p>
            Affixes such as these will fire automatically. How ever enemies can out right
            resist the damage done. All enemies have a % of resistance against affixes. Celestials have a higher
            amount of resistance then regular drop down critters.
          </p>
          <p>
            Unlike artifact Annulment and Spell Evasion, the resistance will not reduce damage done, instead it will
            out right nullify the damage. If the enchantment is marked as irresistible damage, then the enemy cannot resist
            the incoming damage.
          </p>
          <p>
            These affixes will fire, regardless if you miss or hit. These affixes cannot stack unless otherwise stated.
            That means, having multiple will do nothing, you'll take the highest of all non stacking damaging affixes.
          </p>
          <p>
            With the right quest item, you can make all damage from all affixes Irresistible.
          </p>
          <dl>
            <dt>Damage:</dt>
            <dd>{{number_format($itemAffix->damage)}}</dd>
            <dt>Is Damage Irresistible?:</dt>
            <dd>{{$itemAffix->irresistible_damage ? 'Yes' : 'No'}}</dd>
            <dt>Can Stack:</dt>
            <dd>{{$itemAffix->damage_can_stack ? 'Yes' : 'No'}}</dd>
          </dl>
        </div>
      </div>
    @endif
    @if ($itemAffix->reduces_enemy_stats)
      <h2 class="mt-2 mb-2">Stat Reduction</h2>

      <div class="card">
        <div class="card-body">
          <p>
            Affixes that reduce stats can and cannot stack. For example: Prefixes cannot stack, but Suffixes can.
          </p>
          <p>
            If you have multiple prefixes attached that reduce all enemy stats, we will take the first one. Doesn't matter
            what it is.
          </p>
          <p>
            Stat reduction is applied before anything else is done, but can be resisted unless you have the appropriate quest item.
          </p>
          <dl>
            <dt>Str Reduction:</dt>
            <dd>{{$itemAffix->str_reduction * 100}}%</dd>
            <dt>Dex Reduction:</dt>
            <dd>{{$itemAffix->dex_reduction * 100}}%</dd>
            <dt>Dur Reduction:</dt>
            <dd>{{$itemAffix->dur_reduction * 100}}%</dd>
            <dt>Int Reduction:</dt>
            <dd>{{$itemAffix->int_reduction * 100}}%</dd>
            <dt>Chr Reduction:</dt>
            <dd>{{$itemAffix->chr_reduction * 100}}%</dd>
            <dt>Agi Reduction:</dt>
            <dd>{{$itemAffix->agi_reduction * 100}}%</dd>
            <dt>Focus Reduction:</dt>
            <dd>{{$itemAffix->focus_reduction * 100}}%</dd>
          </dl>
        </div>
      </div>
    @endif
    @if (!is_null($itemAffix->steal_life_amount))
      <h2 class="mt-2 mb-2">Life Stealing Amount</h2>

      <div class="card">
        <div class="card-body">
          <p>
            These Affixes can and cannot stack. If you are a vampire they will stack and you have a chance for them to fire twice.
            The first time they can fire is during the attack and the second time is after the enemies round if you or
            the enemy is still alive.
          </p>
          <p>The chance aspect depends on the enemies affix resistance, assuming you do not have the appropriate quest item.</p>
          <p>
            If you are <strong>not</strong> a vampire, these affixes will
            <strong>NOT</strong> stack. Instead we will use your highest and it will only fire after the enemy attack.
          </p>
          <dl>
            <dt>Steal Life Amount:</dt>
            <dd>{{$itemAffix->steal_life_amount * 100}}%</dd>
          </dl>
        </div>
      </div>
    @endif
    @if ($itemAffix->entranced_chance > 0)
      <h2 class="mt-2 mb-2">Entrance Chance</h2>

      <div class="card">
        <div class="card-body">
          <p>
            These Affixes do not stack. You have percentage chance to entrance the enemy so they cannot block or be missed.
          </p>
          <dl>
            <dt>Entrance Chance:</dt>
            <dd>{{$itemAffix->entranced_chance * 100}}%</dd>
          </dl>
        </div>
      </div>
    @endif
    @if ($itemAffix->devouring_light > 0)
      <h2 class="mt-2 mb-2">Devouring Light (Voidance)</h2>

      <div class="card">
        <div class="card-body">
          <p>
            These Affixes do not stack. You have a percentage chance to void the enemy of using their affixes. Some higher level critters
            have a small chance to void you, while Celestials have a much higher chance. If you are voided, you loose all enchantments, no life stealing,
            no modded stats and no boons.
          </p>
          <dl>
            <dt>Devouring Light Chance:</dt>
            <dd>{{$itemAffix->devouring_light * 100}}%</dd>
          </dl>
        </div>
      </div>
    @endif
    @if ($itemAffix->skill_reduction > 0)
      <h2 class="mt-2 mb-2">Skill Reduction</h2>

      <div class="card">
        <div class="card-body">
          <p>
            These Affixes only affect enemies and can reduce ALL their skills at once by a specified %. These affixes work
            in the same vein as stat reduction affixes, how ever these do not stack. We take the best one of all you have on.
          </p>
          <dl>
            <dt>Skills Affected:</dt>
            <dd>Accuracy, Dodge, Casting Accuracy and Criticality</dd>
            <dt>Skills Reduced By:</dt>
            <dd>{{$itemAffix->skill_reduction * 100}}%</dd>
          </dl>
        </div>
      </div>
    @endif
    @if ($itemAffix->resistance_reduction > 0)
      <h2 class="mt-2 mb-2">Resistance Reduction</h2>

      <div class="card">
        <div class="card-body">
          <p>These affixes do not stack and only effect the enemy. These reduce the following resistances that all enemies have:</p>
          <ul>
            <li>Spell Evasion</li>
            <li>Artifact Annulment</li>
            <li>Affix Resistance</li>
          </ul>
          <p>Should you have many equipped, we will take the best one of them all.</p>
          <p>Much like skill reduction and stat reduction these are applied only if you are not voided and before the fight begins.</p>
          <dl>
            <dt>Resistance Reduction:</dt>
            <dd>{{$itemAffix->resistance_reduction * 100}}%</dd>
          </dl>
        </div>
      </div>
    @endif
  </div>
</div>