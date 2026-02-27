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
            Exploration cannot be used here if you want the below rewards. You must manually fight. Except for currencies. You can explore here to gain the currencies.
        </x-core.alerts.warning-alert>
        <p class="mb-4"> This location will let you explore here, for shards to drop off enemies. 1-1000 shards per
            kill.</p>
        <ul class="list-disc my-4">
            <li class="ml-4">Characters can get 1-10,000 Gold from fighting monsters. This can be increased to 20,000 if an event is triggered at this area.</li>
            <li class="ml-4">Characters can get 1-500 Gold Dust from fighting monsters. This can be increased to 1,000 if an event is triggered at this area.</li>
            <li class="ml-4">Characters can get 1-500 Shards from fighting monsters. This can be increased to 1,000 if an event is triggered at this area.</li>
            <li class="ml-4">There is a 1/1,000,000 (+15% Looting) chance to get a random <a href="/information/random-enchants" target="_blank">Medium Unique <i className="fas fa-external-link-alt"></i></a> from Monsters half way down the list of more. This can be reduced to 1/500,000 (+30% looting) chance if an event is triggered at this area.</li>
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
            Exploration cannot be used here if you want the below rewards. You must manually fight. Except for currencies. You can explore here to gain the currencies.
        </x-core.alerts.warning-alert>
        <p class="my-4">In this location, a few things will happen for those who have access:</p>
        <ul class="list-disc my-4">
            <li class="ml-4">Characters can get 1-1000 Gold Dust from fighting monsters. This can be increased to 5,000 if an event is triggered at this area.</li>
            <li class="ml-4">Characters can get 1-1000 Shards from fighting monsters. This can be increased to 5,000 if an event is triggered at this area.</li>
            <li class="ml-4">Characters can get 1-1000 Copper Coins<sup>*</sup> from fighting monsters. This can be increased to 5,000 if an event is triggered at this area.</li>
            <li class="ml-4">There is a 1/1,000 chance to get a Purgatory Chain Unique from Monsters half way down the list of more. This can be reduced to 1/500 chance if an event is triggered at this area.</li>
            <li class="ml-4">There is a 1/1,000 chance to get a Purgatory Chain Mythic from the last monster in the list. This can be reduced to 1/00 chance if an event is triggered at this area.</li>
            <li class="ml-4">There is a 1/1,000 chance to trigger an event while fighting here to reduce the chances and increase the currencies (the above "if an event is triggered") for 1 hour at this location only.</li>
        </ul>
        <p class="mt-4 mb-4 italic"><sup>*</sup> Provided characters have the required quest item to obtain copper coins.</p>
    @endif

    @if ($locationType->isTheOldChurch())
        <h3 class="mb-4">Welcome to The Old Church!</h3>
        <x-core.alerts.warning-alert title="ATTN!">
            Exploration cannot be used here if you want the below rewards. You must manually fight. Except for currencies. You can explore here to gain the currencies.
        </x-core.alerts.warning-alert>
        <x-core.alerts.info-alert title="WAIT!">
            The below only applies to those who poses the Christmas Tree Light Bulb Quest item from completing a quest chain that starts with: Thousands of Years Ago ... and
            ends with: The doors to The Old Church.
        </x-core.alerts.info-alert>
        <p class="my-4">In this location, a few things will happen for those who have access:</p>
        <ul class="list-disc my-4">
            <li class="ml-4">Characters can get 1-1000 Gold Dust from fighting monsters. This can be increased to 5,000 if an event is triggered at this area.</li>
            <li class="ml-4">Characters can get 1-1000 Shards from fighting monsters. This can be increased to 5,000 if an event is triggered at this area.</li>
            <li class="ml-4">Characters can get 1-20,000 Gold from fighting monsters. This can be increased to 40,000 if an event is triggered at this area.</li>
            <li class="ml-4">There is a 1/1,000 chance (+15% of your looting) to get a <a href="/information/unique-items" target="_blank">Unique <i className="fas fa-external-link-alt"></i></a> Corrupted Ice from Monsters halfway down the list of more. This can be reduced to 1/500 (+30% Looting) chance if an event is triggered at this area.</li>
            <li class="ml-4">There is a 1/1,000 chance to trigger an event while fighting here to reduce the chances and increase the currencies (the above "if an event is triggered") for 1 hour at this location only.</li>
        </ul>
    @endif

    @if ($locationType->isCaveOfMemories())
        <h3 class="mb-4">Time to Delve!</h3>
        <dl class="mb-4">
            <dt>Time Between Fights</dt>
            <dd>{{$location->minutes_between_delve_fights}}</dd>
            <dt>Enemy Increase % per Fight</dt>
            <dd>{{$location->delve_enemy_strength_increase * 100}}%</dd>
            <dt>Hours until quest item(s) drops</dt>
            <dd>{{$location->hours_to_drop}}</dd>
        </dl>
        <x-core.alerts.warning-alert title="ATTN!">
            Players can use either Exploration of Delve here. Exploration will fight regular monsters, while Dwleve will fight a random monster each turn and increase said monsters stat by a % every fight making it such that you eventually wont be able to survive.
            Its about how long (max of 8 hours) you can survive.
        </x-core.alerts.warning-alert>
        <x-core.alerts.info-alert title="WAIT!">
            The below only applies to those who use Delve when setting up exploration. Manual fighting and Exploration will not trigger the effects described below.
        </x-core.alerts.info-alert>
        <p class="my-4">In this location, a few things will happen for those who have access and use Delve:</p>
        <ul class="list-disc my-4">
            <li class="ml-4">Quest items can drop for this location but only if you survive for the required time, every fight after that will check if you can get the quest item via your own looting chance.</li>
            <li class="ml-4">Monsters are chosen at random, each fight will increase the monsters stats by 5%, for example the first fight would have 0% increase, the next would have 5% increase, the next fight would have 10% and so on, increasing by 5% each time.</li>
            <li class="ml-4"><strong>You are not intended to survive</strong>. It is about how many hours can you survive? The longer the better rewards you get.</li>
            <li class="ml-4">Even if you die to a monster, you will be rewarded with: 1 Unique for lasting 2 hours or more. 1 Mythic for lasting 4 hours or more. 1 Cosmic for lasting 6 hours or more.</li>
            <li class="ml-4">Delve can be canceled at any time and cannot have a time limit put on it. Its once every 5 minutes for a max of 8 hours.</li>
        </ul>
        <p class="mb-4">These are the monsters you can encounter here while doing delve. These creatures can only be fought during delve. That means you cannot fight manually or through exploration here.</p>
        <div>
        @livewire('admin.items.items-table', [
            'type' => 'quest',
            'locationId' => $location->id,
        ])
        </div>
        <p class="my-8">Below ate the items that can drop here. Above it indicates how long you must survive in delve before the quest items drop. These are tied to your looting chance with any and all other bonuses.</p>
        <div>
        @livewire('admin.items.items-table', [
            'type' => 'quest',
            'locationId' => $location->id,
        ])
        </div>
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

