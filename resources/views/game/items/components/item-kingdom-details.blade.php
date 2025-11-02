<x-core.cards.card-with-title title="Details" buttons="false">
  <p class="my-4 text-sky-600 dark:text-sky-400">
    {{ nl2br($item->description) }}
  </p>

  <x-core.dl.dl>
    <x-core.dl.dt>Kingdom Destruction %</x-core.dl.dt>
    <x-core.dl.dd>{{ $item->kingdom_damage * 100 }}%</x-core.dl.dd>
  </x-core.dl.dl>
</x-core.cards.card-with-title>

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
          <x-core.dl.dt>Gold Cost</x-core.dl.dt>
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
