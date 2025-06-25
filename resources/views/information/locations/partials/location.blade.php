<p class="mb-4">{{ $location->description }}</p>
<dl class="mb-4">
    <dt class="font-medium">Location X Coordinate:</dt>
    <dd class="mb-2">{{ $location->x }}</dd>
    <dt class="font-medium">Location Y Coordinate:</dt>
    <dd class="mb-2">{{ $location->y }}</dd>
    <dt class="font-medium">On Map:</dt>
    <dd class="mb-2">{{ $location->map->name }}</dd>
    <dt class="font-medium">Is Port:</dt>
    <dd class="mb-2">{{ $location->is_port ? 'Yes' : 'No' }}</dd>
    <dt class="font-medium">Increases Enemy Strength By:</dt>
    <dd class="mb-2">
        {{ ! is_null($location->enemy_strength_increase) ? $location->enemy_strength_increase : 'None.' }}
    </dd>
</dl>



@if (! is_null($location->required_quest_item_id))
    <div class="w-3/4 mx-auto border-b border-gray-300 dark:border-gray-600 my-8"></div>

    <x-core.alerts.info-alert title="You need something to enter!">
        <p class="my-2">
            This place requires you to have an item before you enter:
        </p>
        <p class="my-2">
            <a
              href="{{ route('info.page.item', ['item' => $location->required_quest_item_id]) }}"
              class="text-blue-600 dark:text-blue-500 underline focus:outline-none"
            >
                {{ $location->requiredQuestItem->affix_name }}
            </a>
        </p>
    </x-core.alerts.info-alert>
@endif

@if (! is_null($locationType))
    <div class="w-3/4 mx-auto border-b border-gray-300 dark:border-gray-600 my-8"></div>
    
    <div class="mt-4 space-y-6">
        @if ($locationType->isGoldMines())
            @include ('information.locations.partials.gold-mines')
        @endif

        @if ($locationType->isPurgatoryDungeons())
            @include ('information.locations.partials.purgatory-dungeons')
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
    <div class="w-3/4 mx-auto border-b border-gray-300 dark:border-gray-600 my-8"></div>
    <h3 class="text-xl font-semibold mb-4 text-gray-900 dark:text-gray-100">Items that can drop from this location.</h3>
    <p class="mb-4 text-gray-700 dark:text-gray-300">
        If this location is on a plane that effects enemy stats (Shadow Plane,
        Hell, Purgatory, Twisted Memories, Ice Plane and Delusional Memories) then those stat modifications will be taken into
        account along with the locations enemy modifications. Your gear, stats
        and level matter.
    </p>
    <x-core.alerts.warning-alert title="ATTN!">
        Exploration cannot be used here if you want the below rewards. You
        must manually fight. Except for currencies. You can explore here to
        gain the currencies.
    </x-core.alerts.warning-alert>
    @livewire(
        'admin.items.items-table',
        [
            'type' => 'quest',
            'locationId' => $location->id,
        ]
    )
@endif
