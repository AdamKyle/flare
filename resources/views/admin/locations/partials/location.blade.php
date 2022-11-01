<x-core.cards.card-with-title
    title="{{$location->name}}"
    buttons="false"
>
    <p class="mb-4">{{$location->description}}</p>
    <dl class="mb-4">
        <dt>Location X Coordinate:</dt>
        <dd>{{$location->x}}</dd>
        <dt>Location Y Coordinate:</dt>
        <dd>{{$location->y}}</dd>
        <dt>On Map:</dt>
        <dd>{{$location->map->name}}</dd>
        <dt>Is Port:</dt>
        <dd>{{$location->is_port ? 'Yes' : 'No'}}</dd>
        <dt>Increases Enemy Strength By:</dt>
        <dd>{{!is_null($increasesEnemyStrengthBy) ? $increasesEnemyStrengthBy : 'None.'}}</dd>
        <dt>Increases Drop Rate By:</dt>
        <dd>{{$increasesDropChanceBy * 100}}%</dd>
    </dl>

    @if (!is_null($locationType))
        @if ($locationType->isGoldMines())
            <h3 class="mb-4">Welcome to Gold Mines</h3>
            <p class="mb-4"> This location will let you explore here, for shards to drop off enemies. 1-5 shards per kill.</p>
        @endif

        @if ($locationType->isPurgatoryDungeons())
            <h3 class="mb-4">Welcome to Purgatories Dungeons!</h3>
            <p class="mb-4"> This location will let you explore here, for 3x the copper coin drop and a 1/1000 chance for a Mythic Item to drop.</p>
        @endif
    @endif

    @if (!is_null($increasesEnemyStrengthBy))
        <h3 class="mb-4">Items that can drop from this location.</h3>
        <p class="mb-4">
            Auto battle will not allow you to obtain these items. You must manually farm them. These have a 1/100 chance to drop.
            Looting Skill Bonus is capped at 45%.
        </p>
        <p class="mb-4">
            If this location is on a plane that effects enemy stats (Shadow Plane, Hell and Purgatory) then those stat modifications
            will be taken into account along with the locations enemy modifications. Your gear, stats and level matter.
        </p>
        @livewire('admin.items.items-table', [
            'type'       => 'quest',
            'locationId' => $location->id,
        ])
    @endif
</x-core.cards.card-with-title>

@if (!is_null($location->questRewardItem))
    <x-core.alerts.info-alert title="Game Tip">
        <p>
            If a location has a quest reward associated with it, all you have to do is physically
            visit the location to get the quest reward.
        </p>
    </x-core.alerts.info-alert>
    @include('game.items.components.items-quest-details', ['item' => $location->questRewardItem])
@endif
