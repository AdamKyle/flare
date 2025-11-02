<p class="mb-4">{{ $location->description }}</p>

<div
  class="mx-auto my-8 w-3/4 border-b border-gray-300 dark:border-gray-600"
></div>
<div class="m-auto my-4 w-full md:w-2/3">
  <x-core.dl.dl>
    <x-core.dl.dt class="font-medium">Location X Coordinate:</x-core.dl.dt>
    <x-core.dl.dd class="mb-2">{{ $location->x }}</x-core.dl.dd>
    <x-core.dl.dt class="font-medium">Location Y Coordinate:</x-core.dl.dt>
    <x-core.dl.dd class="mb-2">{{ $location->y }}</x-core.dl.dd>
    <x-core.dl.dt class="font-medium">On Map:</x-core.dl.dt>
    <x-core.dl.dd class="mb-2">{{ $location->map->name }}</x-core.dl.dd>
    <x-core.dl.dt class="font-medium">Is Port:</x-core.dl.dt>
    <x-core.dl.dd class="mb-2">
      {{ $location->is_port ? 'Yes' : 'No' }}
    </x-core.dl.dd>
    <x-core.dl.dt class="font-medium">
      Increases Enemy Strength By:
    </x-core.dl.dt>
    <x-core.dl.dd class="mb-2">
      {{ ! is_null($location->enemy_strength_increase) ? $location->enemy_strength_increase : 'None.' }}
    </x-core.dl.dd>
  </x-core.dl.dl>
</div>

@if (! is_null($location->required_quest_item_id))
  <div
    class="mx-auto my-8 w-3/4 border-b border-gray-300 dark:border-gray-600"
  ></div>

  <x-core.alerts.info-alert title="You need something to enter!">
    <p class="my-2">
      This place requires you to have an item before you enter:
    </p>
    <p class="my-2">
      <a
        href="{{ route('info.page.item', ['item' => $location->required_quest_item_id]) }}"
        class="text-blue-600 underline focus:outline-none dark:text-blue-500"
      >
        {{ $location->requiredQuestItem->affix_name }}
      </a>
    </p>
  </x-core.alerts.info-alert>
@endif

@if (! is_null($locationType))
  <div
    class="mx-auto my-8 w-3/4 border-b border-gray-300 dark:border-gray-600"
  ></div>

  <div class="mt-4 space-y-6">
    @if ($locationType->isGoldMines())
      @include('information.locations.partials.gold-mines')
    @endif

    @if ($locationType->isPurgatoryDungeons())
      @include('information.locations.partials.purgatory-dungeons')
    @endif

    @if ($locationType->isPurgatorySmithHouse())
      @include('information.locations.partials.purgatory-smiths-house')
    @endif

    @if ($locationType->isTheOldChurch())
      @include('information.locations.partials.the-old-church')
    @endif
  </div>
@endif

@if (! is_null($location->enemy_strength_increase))
  <div
    class="mx-auto my-8 w-3/4 border-b border-gray-300 dark:border-gray-600"
  ></div>
  <h3 class="mb-4 text-xl font-semibold text-gray-900 dark:text-gray-100">
    Items that can drop from this location.
  </h3>
  <p class="mb-4 text-gray-700 dark:text-gray-300">
    If this location is on a plane that effects enemy stats (Shadow Plane, Hell,
    Purgatory, Twisted Memories, Ice Plane and Delusional Memories) then those
    stat modifications will be taken into account along with the locations enemy
    modifications. Your gear, stats and level matter.
  </p>
  <x-core.alerts.warning-alert title="ATTN!">
    Exploration cannot be used here if you want the below rewards. You must
    manually fight. Except for currencies. You can explore here to gain the
    currencies.
  </x-core.alerts.warning-alert>
  @livewire(
    'admin.items.items-table',
    [
      'type' => 'quest',
      'locationId' => $location->id,
    ]
  )
@endif
