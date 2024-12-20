@php
    use App\Game\Events\Values\EventType;

    $icon = 'ra-bone-bite';
    $href = '';

    if ($eventRunning->getTitleOfEvent() === 'Weekly Celestials') {
        $icon = 'ra-bleeding-eye';
        $href = route('event.type', ['event_type' => 'weekly-celestials']);
    }

    if ($eventRunning->getTitleOfEvent() === 'Weekly Currency Drops') {
        $icon = 'ra-gem';
        $href = route('event.type', ['event_type' => 'weekly-currency-drops']);
    }

    if ($eventRunning->getTitleOfEvent() === 'Weekly Faction Loyalty Event') {
        $icon = 'ra-sheriff';
        $href = route('event.type', ['event_type' => 'weekly-faction-loyalty']);
    }

    if ($eventRunning->getTitleOfEvent() === 'The Winter Event') {
        $icon = 'ra-hourglass';
        $href = route('event.type', ['event_type' => 'the-winter-event']);
    }

    if ($eventRunning->getTitleOfEvent() === 'Delusional Memories Event') {
        $icon = 'ra-hourglass';
        $href = route('event.type', ['event_type' => 'delusional-memories']);
    }

    if ($eventRunning->getTitleOfEvent() === 'Delusional Memories Event') {
        $href = route('event.type', ['event_type' => 'delusional-memories']);
    }

    if ($eventRunning->getTitleOfEvent() === 'The Jester of Time') {
        $href = route('event.type', ['event_type' => 'jester-of-time']);
    }

    if ($eventRunning->getTitleOfEvent() === 'The Smugglers Are Back!') {
        $href = route('event.type', ['event_type' => 'the-smugglers-are-back-raid']);
    }

    if ($eventRunning->getTitleOfEvent() === 'The Ice Queens Reign') {
        $href = route('event.type', ['event_type' => 'ice-queen-raid']);
    }

    if ($eventRunning->getTitleOfEvent() === 'The Frozen King') {
        $href = route('event.type', ['event_type' => 'the-frozen-king-raid']);
    }

    if ($eventRunning->getTitleOfEvent() === 'Corrupted Bishop') {
        $href = route('event.type', ['event_type' => 'corrupted-bishop-raid']);
    }

    if ($eventRunning->getTitleOfEvent() === 'Tlessa\'s Feedback Event') {
        $href = route('event.type', ['event_type' => 'tlessas-feedback-event']);
        $icon = 'ra-campfire';
    }
@endphp

<x-core.cards.feature-card>
    <x-slot:icon>
        <i class="ra {{ $icon }} text-primary-600 relative top-2 md:top-4 right-2 md:right-4"></i>
    </x-slot:icon>
    <x-slot:title>
        <h3 class="text-xl md:text-2xl">{{ $eventRunning->getTitleOfEvent() }}</h3>
    </x-slot:title>

    <p class="my-2 text-sm text-orange-600 dark:text-orange-300 md:text-base">
        <strong>Runs from</strong>: {{ $eventRunning->start_date->format('l, j \of F, Y \a\t g:iA') }}
        <strong>until</strong>: {{ $eventRunning->end_date->format('l, j \of F, Y \a\t g:iA') }}
    </p>

    <p class="mb-4 text-sm md:text-base">
        @if ($eventRunning->getTitleOfEvent() === 'Weekly Celestials')
            Join the Celestials battle! Take them down and gain valuable currencies! Celestials spawn with an 80%
            chance. Just move around the map to engage!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'Weekly Currency Drops')
            Get a boost to currencies like Gold Dust, Crystal Shards, and Copper Coins! Great for advanced crafting.
            This boon also applies to Slots and special locations!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'Weekly Faction Loyalty Event')
            Get a boost to working on your Faction Loyalties. Gain 2 points instead of one. When the fame levels up, the
            requirements for the next level are halved!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'The Winter Event')
            Winter event is here! Enter the temporary map for powerful loot and epic adventures. Settle kingdoms to win
            a full set of Corrupted Ice Gear!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'Delusional Memories Event')
            Dive into Delusional Memories! Explore the temporary map for powerful loot and epic adventures. Settle
            kingdoms to win a full set of Delusional Silver Gear!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'The Jester of Time')
            Join the Jester of Time raid! Head to Southren Port in Delusional Memories and band together! The player who
            kills him gets a new Ancestral item!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'The Smugglers Are Back!')
            Join the Smugglers are Back raid! Head to Smugglers Port on Surface and band together! The player who kills
            him gets a new Ancestral item!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'The Ice Queens Reign')
            Join The Ice Queens Reign raid! Head to The Fathers Tomb on The Ice Plane and band together! The player
            who kills her gets a new Ancestral item!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'The Frozen King')
            Join The Frozen King raid! head to The Frozen Christmas Tree Lot on The Ice Plane and band together! The player
            who kills him gets a new Ancestral item!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'Corrupted Bishop')
            Join the Corrupted Bishop raid! head down to the Delusional Memories Federation Controlled Town to band together!
            The Player who kills him gets a new Ancestral item!
        @endif

        @if ($eventRunning->getTitleOfEvent() === 'Tlessa\'s Feedback Event')
            Tlessa wants your feedback! We offer increased XP, 80% drop rate for everyone! and a Mythical item for taking a survey!
        @endif


    </p>
    <div class="text-center">
        <x-core.buttons.link-buttons.primary-button href="{{ $href }}">
            View More Info
        </x-core.buttons.link-buttons.primary-button>
    </div>
</x-core.cards.feature-card>
