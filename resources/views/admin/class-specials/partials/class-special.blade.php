<x-core.layout.info-container>
  @php
    $backUrl = route('class-specials.list');

    if (
      is_null(auth()->user()) ||
      ! auth()
        ->user()
        ->hasRole('Admin')
    ) {
      $backUrl = '/information/class-ranks';
    }
  @endphp

  <x-core.cards.card-with-title
    title="{{$classSpecial->name}}"
    buttons="true"
    backUrl="{{$backUrl}}"
    editUrl="{{route('class-specials.edit', ['gameClassSpecial' => $classSpecial])}}"
  >
    <div>
      <p class="my-4">
        {{ $classSpecial->description }}
      </p>
      <div class="grid grid-cols-2 gap-2">
        <div>
          <h3>Basics</h3>
          <div
            class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
          ></div>
          <dl>
            <dt>Belong to class:</dt>
            <dd>{{ $classSpecial->gameClass->name }}</dd>
            <dt>Requires Class Rank Level:</dt>
            <dd>{{ $classSpecial->requires_class_rank_level }}</dd>
          </dl>
          <h3 class="my-4">Damage</h3>
          <p>
            The damage to this specialty will be done only after your attack,
            with a weapon lands. That is: Attack, Cast and Attack or Attack and
            Cast will fire off the damage of this specialty if: your attack
            lands and is not blocked.
          </p>
          <div
            class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
          ></div>
          <dl>
            <dt>Required Attack Type</dt>
            <dd>
              {{ ucwords(str_replace('_', ' ', $classSpecial->attack_type_required)) }}
            </dd>
            <dt>Damage Amount:</dt>
            <dd>
              {{ ! is_null($classSpecial->specialty_damage) ? $classSpecial->specialty_damage : 0 }}
            </dd>
            <dt>Damage Increase per Level:</dt>
            <dd>
              {{ ! is_null($classSpecial->increase_specialty_damage_per_level) ? $classSpecial->increase_specialty_damage_per_level : 0 }}
            </dd>
            <dt>Damage done uses % of damage stat:</dt>
            <dd>
              {{ $classSpecial->specialty_damage_uses_damage_stat_amount * 100 }}%
            </dd>
          </dl>
          <div
            class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
          ></div>
          <h3 class="my-4">Evasion Bonus</h3>
          <p>
            This will be applied to your spell evasion, which comes primarily
            from rings.
          </p>
          <div
            class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
          ></div>
          <dl class="mb-4">
            <dt>Spell Evasion</dt>
            <dd>{{ $classSpecial->spell_evasion * 100 }}%</dd>
          </dl>
        </div>
        <div
          class="my-6 block border-b-2 border-b-gray-300 lg:hidden dark:border-b-gray-600"
        ></div>
        <div>
          <h3>Modifiers</h3>
          <div
            class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
          ></div>
          <dl class="mb-4">
            <dt>Base Damage Modifier</dt>
            <dd>{{ $classSpecial->base_damage_mod * 100 }}%</dd>
            <dt>Base AC Modifier</dt>
            <dd>{{ $classSpecial->base_ac_mod * 100 }}%</dd>
            <dt>Base Healing Modifier</dt>
            <dd>{{ $classSpecial->base_healing_mod * 100 }}%</dd>
            <dt>Base Spell Damage Modifier</dt>
            <dd>{{ $classSpecial->base_spell_damage_mod * 100 }}%</dd>
            <dt>Base Health Modifier</dt>
            <dd>{{ $classSpecial->health_mod * 100 }}%</dd>
            <dt>Base Damage Stat Increase</dt>
            <dd>{{ $classSpecial->base_damage_stat_increase * 100 }}%</dd>
          </dl>
          <h3>Reductions</h3>
          <div
            class="my-6 border-b-2 border-b-gray-300 dark:border-b-gray-600"
          ></div>
          <dl>
            <dt>Affix Damage Reduction</dt>
            <dd>{{ $classSpecial->affix_damage_reduction * 100 }}%</dd>
            <dt>Healing Reduction</dt>
            <dd>{{ $classSpecial->healing_reduction * 100 }}%</dd>
            <dt>Skill Reduction</dt>
            <dd>{{ $classSpecial->skill_reduction * 100 }}%</dd>
            <dt>Resistance Reduction</dt>
            <dd>{{ $classSpecial->resistance_reduction * 100 }}%</dd>
          </dl>
        </div>
      </div>
    </div>
  </x-core.cards.card-with-title>
</x-core.layout.info-container>
