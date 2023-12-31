<p class="mb-4">{{ $location->description }}</p>
<dl class="mb-4">
    <dt>Location X Coordinate:</dt>
    <dd>{{ $location->x }}</dd>
    <dt>Location Y Coordinate:</dt>
    <dd>{{ $location->y }}</dd>
    <dt>On Map:</dt>
    <dd>{{ $location->map->name }}</dd>
    <dt>Is Port:</dt>
    <dd>{{ $location->is_port ? 'Yes' : 'No' }}</dd>
    <dt>Increases Enemy Strength By:</dt>
    <dd>{{ !is_null($increasesEnemyStrengthBy) ? $increasesEnemyStrengthBy : 'None.' }}</dd>
    <dt>Increases Drop Rate By:</dt>
    <dd>{{ $increasesDropChanceBy * 100 }}%</dd>
</dl>

@if (!is_null($location->required_quest_item_id))
    <x-core.alerts.info-alert title="You need something to enter!">
        <p class="my-2">
            This place requires you to have an item before you enter:
        </p>
        <p class="my-2">
            <a href="{{ route('info.page.item', ['item' => $location->required_quest_item_id]) }}">
                {{ $location->requiredQuestItem->affix_name }}
            </a>
        </p>

    </x-core.alerts.info-alert>
@endif

@if (!is_null($locationType))
    @if ($locationType->isGoldMines())
        <h3 class="mb-4">Welcome to Gold Mines</h3>
        <x-core.alerts.warning-alert title="ATTN!">
            <strong>Exploration cannot be used here if you want the below rewards. You must manually fight.</strong>
        </x-core.alerts.warning-alert>
        <p class="mb-4"> This location will let you explore here, for shards to drop off enemies. 1-1000 shards per
            kill.</p>
        <ul class="list-disc my-4">
            <li class="ml-4">Characters can get 1-10,000 Gold from fighting monsters. This can be increased to 50,000 if an event is triggered at this area.</li>
            <li class="ml-4">Characters can get 1-500 Gold Dust from fighting monsters. This can be increased to 1,000 if an event is triggered at this area.</li>
            <li class="ml-4">Characters can get 1-500 Shards from fighting monsters. This can be increased to 1,000 if an event is triggered at this area.</li>
            <li class="ml-4">There is a 1/1,000,000 chance to get a random <a href="/information/random-enchants" target="_blank">Medium Unique <i className="fas fa-external-link-alt"></i></a> from Monsters half way down the list of more. This can be reduced to 1/500,000 chance if an event is triggered at this area.</li>
            <li class="ml-4">There is a 1/1,000,000 chance to trigger an event while fighting here to reduce the chances and increase the currencies (the above "if an event is triggered") for 1 hour at this location only.</li>
        </ul>
    @endif

    @if ($locationType->isPurgatoryDungeons())
        <h3 class="mb-4">Welcome to Purgatories Dungeons!</h3>
        <p class="mb-4"> This location will let you explore here, for 3x the copper coin drop and a 1/1000 chance for a
            Mythic Item to drop.</p>
    @endif

    @if ($locationType->isPurgatorySmithHouse())
        <h3 class="mb-4">Welcome to Purgatory Smith House!</h3>
        <x-core.alerts.warning-alert title="ATTN!">
            <strong>Exploration cannot be used here if you want the below rewards. You must manually fight.</strong>
        </x-core.alerts.warning-alert>
        <p class="my-4">In this location, a few things will happen for those who have access:</p>
        <ul class="list-disc my-4">
            <li class="ml-4">Characters can get 1-1000 Gold Dust from fighting monsters. This can be increased to 5,000 if an event is triggered at this area.</li>
            <li class="ml-4">Characters can get 1-1000 Shards from fighting monsters. This can be increased to 5,000 if an event is triggered at this area.</li>
            <li class="ml-4">Characters can get 1-1000 Copper Coins<sup>*</sup> from fighting monsters. This can be increased to 5,000 if an event is triggered at this area.</li>
            <li class="ml-4">There is a 1/1,000,000 chance to get a Purgatory Chain Legendary Unique from Monsters half way down the list of more. This can be reduced to 1/500,000 chance if an event is triggered at this area.</li>
            <li class="ml-4">There is a 1/10,000,000 chance to get a Purgatory Chain Mythic from the last monster in the list. This can be reduced to 1/5,000,000 chance if an event is triggered at this area.</li>
            <li class="ml-4">There is a 1/1,000,000 chance to trigger an event while fighting here to reduce the chances and increase the currencies (the above "if an event is triggered") for 1 hour at this location only.</li>
        </ul>
        <p class="mt-4 mb-4 italic"><sup>*</sup> Provided characters have the required quest item to obtain copper coins.</p>

    @endif
@endif

@if (!is_null($increasesEnemyStrengthBy))
    <h3 class="mb-4">Items that can drop from this location.</h3>
    <p class="mb-4">
        Auto battle will not allow you to obtain these items. You must manually farm them. These have a 1/100 chance to
        drop.
        Looting Skill Bonus is capped at 45%.
    </p>
    <p class="mb-4">
        If this location is on a plane that effects enemy stats (Shadow Plane, Hell and Purgatory) then those stat
        modifications
        will be taken into account along with the locations enemy modifications. Your gear, stats and level matter.
    </p>
    @livewire('admin.items.items-table', [
        'type' => 'quest',
        'locationId' => $location->id,
    ])
@endif
</p>
