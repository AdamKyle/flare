@php
    $icon = 'ra-bone-bite';

    if ($eventRunning->getTitleOfEvent() === 'Weekly Celestials') {
        $icon = 'ra-bleeding-eye';
    }

    if ($eventRunning->getTitleOfEvent() === 'Weekly Currency Drops') {
        $icon = 'ra-gem';
    }

    if ($eventRunning->getTitleOfEvent() === 'Weekly Faction Loyalty Event') {
        $icon = 'ra-sheriff';
    }

    if ($eventRunning->getTitleOfEvent() === 'Monthly PVP') {
        $icon = 'ra-lightning-sword';
    }

    if ($eventRunning->getTitleOfEvent() === 'The Ice Queen\'s Realm' ||
        $eventRunning->getTitleOfEvent() === 'Delusional Memories Event') {
        $icon = 'ra-hourglass';
    }
@endphp


<x-core.cards.feature-card>
    <x-slot:icon>
        <i class="ra {{$icon}} text-primary-600 relative top-[10px] right-[10px]"></i>
    </x-slot:icon>
    <x-slot:title>
        <h3>{{$eventRunning->getTitleOfEvent()}}</h3>
    </x-slot:title>

    <p class="text-orange-600 dark:text-orange-300 my-2">
        <strong>Runs from</strong>: {{$eventRunning->start_date->format('l, j \of F, Y \a\t g:iA')}} <strong>until</strong>: {{$eventRunning->end_date->format('l, j \of F, Y \a\t g:iA')}}
    </p>

    <p>
        @if ($eventRunning->getTitleOfEvent() === 'Weekly Celestials')
            Join the Celestials battle! Take them down and gain valuable currencies! Celestials spawn with an 80% chance. Just move around the map to engage!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'Weekly Currency Drops')
            Get a boost to currencies like Gold Dust, Crystal Shards, and Copper Coins! Great for advanced crafting. This boon also applies to Slots and special locations!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'Weekly Faction Loyalty Event')
            Get a boost to working on your Faction Loyalties. Gain 2 points instead of one. When the fame levels up, the requirements for the next level are halved!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'Monthly PVP')
            Monthly PVP is ongoing. Opt in now! Winner gets a mythical item, while participants get 2 billion gold and a medium unique!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'The Ice Queen\'s Realm')
            Winter event is here! Enter the temporary map for powerful loot and epic adventures. Settle kingdoms to win a full set of Corrupted Ice Gear!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'Delusional Memories Event')
            Dive into Delusional Memories! Explore the temporary map for powerful loot and epic adventures. Settle kingdoms to win a full set of Delusional Silver Gear!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'The Jester of Time')
            Join the Jester of Time raid! Head to Southren Port in Delusional Memories and band together! The player who kills him gets a new ancestral item!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'The Smugglers Are Back!')
            Join the Smugglers are Back raid! Head to Smugglers Port on Surface and band together! The player who kills him gets a new ancestral item!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'The Ice Queens Reign')
            Join the The Ice Queens Reign raid! Head to The Fathers Tomb on The Ice Plane and band together! The player who kills him gets a new ancestral item!
        @endif

    </p>
</x-core.cards.feature-card>