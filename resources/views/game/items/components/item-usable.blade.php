<x-core.cards.card-with-title title="Details" buttons="false">
  <p class="my-4 text-sky-600 dark:text-sky-400">
    {{ nl2br($item->description) }}
  </p>

  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <x-core.dl.dl>
    <x-core.dl.dt>Can Stack? (Allows you to use multiple at once)</x-core.dl.dt>
    <x-core.dl.dd>{{ $item->can_stack ? 'Yes' : 'No' }}</x-core.dl.dd>
    <x-core.dl.dt>Lasts for (Minutes)</x-core.dl.dt>
    <x-core.dl.dd>{{ $item->lasts_for }}</x-core.dl.dd>
  </x-core.dl.dl>

  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <x-core.dl.dl>
    <x-core.dl.dt>Xp bonus per kill</x-core.dl.dt>
    <x-core.dl.dd>{{ $item->xp_bonus * 100 }}%</x-core.dl.dd>
    <x-core.dl.dt>Gain additional level on level up?</x-core.dl.dt>
    <x-core.dl.dd>{{ $item->gains_additional_level ? 'Yes' : 'No' }}</x-core.dl.dd>
  </x-core.dl.dl>

  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid gap-3 md:grid-cols-3">
    <div>
      <strong>Stats</strong>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <x-core.dl.dl>
        <x-core.dl.dt>All Stat increase %</x-core.dl.dt>
        <x-core.dl.dd>{{ $item->increase_stat_by * 100, 2 }}%</x-core.dl.dd>
      </x-core.dl.dl>
    </div>
    <div
      class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
    ></div>
    <div>
      <strong>Modifiers</strong>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <x-core.dl.dl>
        <x-core.dl.dt>Base Damage Mod</x-core.dl.dt>
        <x-core.dl.dd>{{ $item->base_damage_mod * 100 }} %</x-core.dl.dd>
        <x-core.dl.dt>Base Ac Mod</x-core.dl.dt>
        <x-core.dl.dd>{{ $item->base_ac_mod * 100 }} %</x-core.dl.dd>
        <x-core.dl.dt>Base Healing Mod</x-core.dl.dt>
        <x-core.dl.dd>{{ $item->base_healing_mod * 100 }} %</x-core.dl.dd>
      </x-core.dl.dl>
    </div>
    <div
      class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
    ></div>
    <div>
      <strong>Skill Modifiers</strong>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      @php
        $skillBonus = $item->increase_skill_bonus_by * 100;
        $skillTrainingBonus = $item->increase_skill_training_bonus_by * 100;
      @endphp

      <x-core.dl.dl>
        <x-core.dl.dt>Effects Skills:</x-core.dl.dt>
        <x-core.dl.dd>
          {{ ! is_null($item->affects_skill_type) ? implode(',', $skills) : 'N/A' }}
        </x-core.dl.dd>
        <x-core.dl.dt>Skill Bonus</x-core.dl.dt>
        <x-core.dl.dd>{{ $skillBonus > 100 ? 100 : $skillBonus }}%</x-core.dl.dd>
        <x-core.dl.dt>Skill XP Bonus</x-core.dl.dt>
        <x-core.dl.dd>{{ $skillTrainingBonus > 100 ? 100 : $skillTrainingBonus }}%</x-core.dl.dd>
      </x-core.dl.dl>
    </div>
  </div>
</x-core.cards.card-with-title>

@if ($item->can_craft)
  <x-core.cards.card css="mb-4">
    <div class="grid gap-3 md:grid-cols-2">
      <div>
        <strong>Crafting Information</strong>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
        <x-core.dl.dl>
          <x-core.dl.dt>Skill Required</x-core.dl.dt>
          <x-core.dl.dd>
            @if ($item->crafting_type !== 'trinketry' || $item->crafting_type !== 'alchemy')
              {{ ucfirst($item->crafting_type) }}
            @else
              {{ ucfirst($item->crafting_type) }} Crafting
            @endif
          </x-core.dl.dd>
          <x-core.dl.dt>Skill Level Required</x-core.dl.dt>
          <x-core.dl.dd>{{ $item->skill_level_required }}</x-core.dl.dd>
          <x-core.dl.dt>Becomes Trivial at (no XP)</x-core.dl.dt>
          <x-core.dl.dd>{{ $item->skill_level_trivial }}</x-core.dl.dd>
        </x-core.dl.dl>
      </div>
      <div
        class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
      ></div>
      <div>
        <strong>Crafting Cost</strong>
        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>
        <x-core.dl.dl>
          @if (! is_null($item->gold_cost) || $item->gold_cost > 0)
            <x-core.dl.dt>Gold Cost</x-core.dl.dt>
            <x-core.dl.dd>{{ number_format($item->gold_cost) }}</x-core.dl.dd>
          @endif

          @if (! is_null($item->gold_dust_cost) || $item->gold_dust_cost > 0)
            <x-core.dl.dt>Gold Dust Cost</x-core.dl.dt>
            <x-core.dl.dd>{{ number_format($item->gold_dust_cost) }}</x-core.dl.dd>
          @endif

          @if (! is_null($item->shards_cost) || $item->shards_cost > 0)
            <x-core.dl.dt>Shard Cost</x-core.dl.dt>
            <x-core.dl.dd>{{ number_format($item->shards_cost) }}</x-core.dl.dd>
          @endif

          @if (! is_null($item->copper_coin_cost) || $item->copper_coin_cost > 0)
            <x-core.dl.dt>Gold Cost</x-core.dl.dt>
            <x-core.dl.dd>{{ number_format($item->copper_coin_cost) }}</x-core.dl.dd>
          @endif
        </x-core.dl.dl>
      </div>
    </div>
  </x-core.cards.card>
@endif
