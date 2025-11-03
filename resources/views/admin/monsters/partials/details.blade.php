<x-core.cards.card-with-title title="Monster Stats" buttons="false">
  <div class="grid gap-4 md:grid-cols-2">
    <div>
      <strong>Stats</strong>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <x-core.dl.dl>
        <x-core.dl.dt>str</x-core.dl.dt>
        <x-core.dl.dd>{{ number_format($monster->str) }}</x-core.dl.dd>
        <x-core.dl.dt>dex</x-core.dl.dt>
        <x-core.dl.dd>{{ number_format($monster->dex) }}</x-core.dl.dd>
        <x-core.dl.dt>dur</x-core.dl.dt>
        <x-core.dl.dd>{{ number_format($monster->dur) }}</x-core.dl.dd>
        <x-core.dl.dt>chr</x-core.dl.dt>
        <x-core.dl.dd>{{ number_format($monster->chr) }}</x-core.dl.dd>
        <x-core.dl.dt>int</x-core.dl.dt>
        <x-core.dl.dd>{{ number_format($monster->int) }}</x-core.dl.dd>
        <x-core.dl.dt>agi</x-core.dl.dt>
        <x-core.dl.dd>{{ number_format($monster->int) }}</x-core.dl.dd>
        <x-core.dl.dt>focus</x-core.dl.dt>
        <x-core.dl.dd>{{ number_format($monster->int) }}</x-core.dl.dd>
        <x-core.dl.dt>Damage Stat</x-core.dl.dt>
        <x-core.dl.dd>{{ $monster->damage_stat }}</x-core.dl.dd>
      </x-core.dl.dl>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <strong>Skills</strong>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <x-core.dl.dl>
        <x-core.dl.dt>Accuracy</x-core.dl.dt>
        <x-core.dl.dd>{{ $monster->accuracy * 100 }}%</x-core.dl.dd>
        <x-core.dl.dt>Casting Accuracy</x-core.dl.dt>
        <x-core.dl.dd>{{ $monster->casting_accuracy * 100 }}%</x-core.dl.dd>
        <x-core.dl.dt>Criticality</x-core.dl.dt>
        <x-core.dl.dd>{{ $monster->criticality * 100 }}%</x-core.dl.dd>
        <x-core.dl.dt>Dodge</x-core.dl.dt>
        <x-core.dl.dd>{{ $monster->dodge * 100 }}%</x-core.dl.dd>
      </x-core.dl.dl>
    </div>
    <div
      class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
    ></div>
    <div>
      <strong>Health/Damage/AC</strong>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <x-core.dl.dl>
        <x-core.dl.dt>Health Range</x-core.dl.dt>
        <x-core.dl.dd>
          {{ number_format(explode('-', $monster->health_range)[0]) }}
          -
          {{ number_format(explode('-', $monster->health_range)[1]) }}
        </x-core.dl.dd>
        <x-core.dl.dt>Attack Range</x-core.dl.dt>
        <x-core.dl.dd>
          {{ number_format(explode('-', $monster->attack_range)[0]) }}
          -
          {{ number_format(explode('-', $monster->attack_range)[1]) }}
        </x-core.dl.dd>
        <x-core.dl.dt>AC</x-core.dl.dt>
        <x-core.dl.dd>{{ number_format($monster->ac) }}</x-core.dl.dd>
      </x-core.dl.dl>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <strong>Reward Details</strong>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <x-core.dl.dl>
        <x-core.dl.dt>Drop Chance</x-core.dl.dt>
        <x-core.dl.dd>{{ $monster->drop_check * 100 }}%</x-core.dl.dd>
        <x-core.dl.dt>XP</x-core.dl.dt>
        <x-core.dl.dd>{{ $monster->xp }}</x-core.dl.dd>
        <x-core.dl.dt>
          Max Level
          <sup>*</sup>
        </x-core.dl.dt>
        <x-core.dl.dd>{{ $monster->max_level }}</x-core.dl.dd>
        <x-core.dl.dt>Gold Reward</x-core.dl.dt>
        <x-core.dl.dd>{{ number_format($monster->gold) }}</x-core.dl.dd>
      </x-core.dl.dl>
      <p class="mt-4">
        <sup>*</sup>
        Indicates that if you are over this level, you only get 1/3
        <sup>rd</sup>
        the monster's XP
      </p>
    </div>
  </div>
  <div class="grid gap-4 md:grid-cols-2">
    <div>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <strong>Resistances</strong>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <x-core.dl.dl>
        <x-core.dl.dt>Affix Resistance (chance):</x-core.dl.dt>
        <x-core.dl.dd>{{ $monster->affix_resistance * 100 }}%</x-core.dl.dd>
        <x-core.dl.dt>Spell Evasion (chance):</x-core.dl.dt>
        <x-core.dl.dd>{{ $monster->spell_evasion * 100 }}%</x-core.dl.dd>
        <x-core.dl.dt>Life Stealing Resistance:</x-core.dl.dt>
        <x-core.dl.dd>
          {{ is_null($monster->life_stealing_resistance) ? 0 : $monster->life_stealing_resistance * 100 }}%
        </x-core.dl.dd>
      </x-core.dl.dl>
    </div>
    <div>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <strong>Devouring Light/Darkness</strong>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <div>
        <x-core.dl.dl>
          <x-core.dl.dt>Devouring Light Chance:</x-core.dl.dt>
          <x-core.dl.dd>{{ $monster->devouring_light_chance * 100 }}%</x-core.dl.dd>
          <x-core.dl.dt>Devouring Darkness Chance:</x-core.dl.dt>
          <x-core.dl.dd>{{ $monster->devouring_darkness_chance * 100 }}%</x-core.dl.dd>
        </x-core.dl.dl>
      </div>
    </div>
  </div>
</x-core.cards.card-with-title>

<x-core.cards.card-with-title title="Cast and Affixes">
  <x-core.dl.dl class="mt-3">
    <x-core.dl.dt>Max Cast For</x-core.dl.dt>
    <x-core.dl.dd>{{ number_format($monster->max_spell_damage) }}</x-core.dl.dd>
    <x-core.dl.dt>Max Affix Damage</x-core.dl.dt>
    <x-core.dl.dd>{{ number_format($monster->max_affix_damage) }}</x-core.dl.dd>
    <x-core.dl.dt>Healing Percentage</x-core.dl.dt>
    <x-core.dl.dd>{{ $monster->healing_percentage * 100 }}%</x-core.dl.dd>
    <x-core.dl.dt>Entrancing Chance</x-core.dl.dt>
    <x-core.dl.dd>{{ $monster->entrancing_chance * 100 }}%</x-core.dl.dd>
  </x-core.dl.dl>
</x-core.cards.card-with-title>
<hr />
<x-core.cards.card-with-title title="Ambush & Counter">
  <x-core.dl.dl class="mt-3">
    <x-core.dl.dt>Ambush Chance</x-core.dl.dt>
    <x-core.dl.dd>{{ $monster->ambush_chance * 100 }}%</x-core.dl.dd>
    <x-core.dl.dt>Ambush Resistance Chance</x-core.dl.dt>
    <x-core.dl.dd>{{ $monster->ambush_resistance * 100 }}%</x-core.dl.dd>
    <x-core.dl.dt>Counter Chance</x-core.dl.dt>
    <x-core.dl.dd>{{ $monster->counter_chance * 100 }}%</x-core.dl.dd>
    <x-core.dl.dt>Counter Resistance Chance</x-core.dl.dt>
    <x-core.dl.dd>{{ $monster->counter_resistance * 100 }}%</x-core.dl.dd>
  </x-core.dl.dl>
</x-core.cards.card-with-title>

@if (! is_null($monster->fire_atonement) && ! is_null($monster->ice_atonement) && ! is_null($monster->water_atonement))
  <x-core.cards.card-with-title title="Elemental Atunement">
    <p class="mb-3">
      Elemental Atunement is simmilar to how
      <a href="/information/gems" target="_blank">
        <i class="fas fa-external-link-alt"></i>
        Gems
      </a>
      work for characters. The higher the value, the more attuned to thay
      element the critter is, which means tier elemental attack will be of that
      type. If your elemental atunement is the oppisite, for example Water is
      oppisite to Fire, you will only take half the damage, If your element is
      weak against their, ie: They are fire, you are ice, you will take twice
      the amount of damage.
    </p>
    <p>Your armour class can block the damage, if it is high enough.</p>
    <x-core.dl.dl class="mt-3">
      <x-core.dl.dt>Fire Atunement</x-core.dl.dt>
      <x-core.dl.dd>{{ $monster->fire_atonement * 100 }}%</x-core.dl.dd>
      <x-core.dl.dt>Ice Atunement</x-core.dl.dt>
      <x-core.dl.dd>{{ $monster->ice_atonement * 100 }}%</x-core.dl.dd>
      <x-core.dl.dt>Water Atunement</x-core.dl.dt>
      <x-core.dl.dd>{{ $monster->water_atonement * 100 }}%</x-core.dl.dd>
    </x-core.dl.dl>
  </x-core.cards.card-with-title>
@endif

@if ($monster->is_celestial_entity)
  <hr />
  <x-core.cards.card-with-title title="Celestial Conjuration Cost/Reward">
    <x-core.dl.dl class="mt-3">
      @if ($monster->gold_cost > 0)
        <x-core.dl.dt>Gold Cost:</x-core.dl.dt>
        <x-core.dl.dd>{{ number_format($monster->gold_cost) }}</x-core.dl.dd>
      @endif

      @if ($monster->gold_dust_cost > 0)
        <x-core.dl.dt>Gold Dust Cost:</x-core.dl.dt>
        <x-core.dl.dd>{{ number_format($monster->gold_dust_cost) }}</x-core.dl.dd>
      @endif

      <x-core.dl.dt>Shard Reward:</x-core.dl.dt>
      <x-core.dl.dd>{{ number_format($monster->shards) }}</x-core.dl.dd>
    </x-core.dl.dl>
  </x-core.cards.card-with-title>
@endif
