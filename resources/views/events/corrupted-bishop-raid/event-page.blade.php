@extends('layouts.app')

@push('head')
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css"
    />
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
@endpush

@section('content')
    <div class="container mx-auto lg:px-4 mb-5">
        <div class="mb-10 lg:mt-10">
            <div class="text-center">
                <h3
                    class="mb-5 font-thin text-5xl dark:text-gray-300 text-gray-800 text-4xl md:text-7xl"
                >
                    Corrupted Bishop
                </h3>
                <p class="mb-5 dark:text-gray-300 text-gray-800 italic">
                    A mad bishop of a time long lost who corrupts his own
                    delusional memories!
                </p>
            </div>

            @if (! is_null($event))
                <p
                    class="mb-10 text-orange-600 dark:text-orange-300 my-2 text-center"
                >
                    <strong>Runs from</strong>
                    :
                    {{ $event->start_date->format('l, j \of F, Y \a\t g:iA') }}
                    <strong>until</strong>
                    : {{ $event->end_date->format('l, j \of F, Y \a\t g:iA') }}
                </p>
            @else
                <div class="w-1/3 mx-auto">
                    <x-core.alerts.info-alert title="Not yet scheduled">
                        This event hasn't been scheduled yet. Don't worry The
                        Creator will schedule it soon! Below you can learn more
                        about it for when it is scheduled!
                    </x-core.alerts.info-alert>
                </div>
            @endif
            <div
                class="text-center grid md:grid-cols-3 gap-2 md:w-2/3 w-full mr-auto ml-auto"
            >
                <x-core.buttons.link-buttons.primary-button
                    css="mr-2"
                    href="{{ route('register') }}"
                >
                    Join Today!
                </x-core.buttons.link-buttons.primary-button>
                <x-core.buttons.link-buttons.success-button
                    css="mr-2"
                    href="{{ route('info.page', ['pageName' => 'home']) }}"
                >
                    Learn More (About the game)
                </x-core.buttons.link-buttons.success-button>
                <x-core.buttons.link-buttons.orange-button
                    css="mr-2"
                    href="{{ route('releases.list') }}"
                >
                    Game Release Notes
                </x-core.buttons.link-buttons.orange-button>
            </div>
        </div>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <h2
                class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800"
            >
                <i class="ra ra-death-skull"></i>
                The Corrupted Bishop will use his alchemy to weave his own
                delusions into your nightmares!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                During this
                <a href="/information/raids">raid</a>
                players have a chance to participate in raid quests that flush
                out more of the story from
                <a href="/information/planes">Delusional Memories</a>
                event. Players can enter corrupted locations and put their might
                to the test as they work together to take down the raid boss!
            </p>

            <p class="mb-10 dark:text-gray-300 text-gray-800">
                <strong>Best part?</strong>
                it's all free! Just requires time investment, as little or as
                much as you want!
            </p>
        </div>

        <div
            class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 md:mt-20"
        >
            <div class="mt-4 lg:mt-0">
                <img
                    src="{{ asset('promotion/corrupted-bishop-raid/raid-locations.png') }}"
                    class="shadow rounded max-w-full h-auto align-middle border-none img-fluid glightbox w-100 mb-5 cursor-pointer"
                />
                <div class="text-center text-sm">Click to make larger.</div>
            </div>
            <div class="md:flex md:items-center text-center md:text-left">
                <div>
                    <h2
                        class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800"
                    >
                        Raid icons appear!
                    </h2>
                    <p class="mb-10 dark:text-gray-300 text-gray-800">
                        During the raid, specific locations will become
                        corrupted. This means the monsters are much stronger and
                        the reward is that much greater. One of these locations
                        contains the raid boss that players work together to
                        take down
                    </p>
                </div>
            </div>
        </div>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <h2
                class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800"
            >
                <span class="fa-stack">
                    <i class="ra ra-monster-skull mr-2"></i>
                </span>
                New
                <a href="/information/quests">Quests</a>
                !
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                Participate in two branches of quests! One will tell the story
                of The Jester and requires your ability to kill raid monsters to
                get the quest items, and the other quest line will lead to a
                cosmetic unlock. In this case players can take their inventory
                max from 75 (including
                <a href="/information/alchemy">Alchemy items</a>
                ,
                <a href="/information/gems">gems</a>
                and regular items) to 150! Allowing you to have more items in
                your inventory. Of course you also have
                <a href="/information/equipment-sets">sets</a>
                which let you have unlimited items!
            </p>
        </div>

        <div
            class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 md:mt-20"
        >
            <div class="md:flex md:items-center text-center md:text-left">
                <div>
                    <h2
                        class="mb-5 font-thin md:text-4xl lg:text-5xl dark:text-gray-300 text-gray-800"
                    >
                        Put your skill to the test!
                    </h2>
                    <p class="mb-4 dark:text-gray-300 text-gray-800">
                        <a
                            href="/information/monsters?table-filters[maps]=Delusional+Memories+Raid+Monsters#no-link"
                        >
                            Raid Critters
                        </a>
                        are much stronger then you might be use to. While the
                        <a
                            href="/information/monsters?table-filters[maps]=Delusional+Memories+Raid+Bosses"
                        >
                            Raid Boss
                        </a>
                        needs players to take it down, the raid critters will
                        put your gear to the test. Just take a look for your
                        self.
                    </p>
                    <p class="mb-10 dark:text-gray-300 text-gray-800">
                        <strong>Are you new?</strong>
                        Don't fret, you can even participate in the Raid Boss
                        fight, while the raid critters might be too strong for
                        you, the raid boss can be participated in by all players
                        of all skill and level. Highend players are imited in
                        their damage output to make things fun and balanced!
                    </p>
                </div>
            </div>
            <div class="mt-4 lg:mt-0">
                <img
                    src="{{ asset('promotion/corrupted-bishop-raid/corrupted-bishop-raid.png') }}"
                    class="shadow rounded max-w-full h-auto align-middle border-none img-fluid glightbox w-100 mb-5 cursor-pointer"
                />
                <div class="text-center text-sm">Click to make larger.</div>
            </div>
        </div>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <h2
                class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800"
            >
                <span class="fa-stack">
                    <i class="fas fa-shopping-bag"></i>
                </span>
                Increase your inventory from 75 to 150 slots!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                Complete the Raid Quests on Delusional Memories Plane and gain
                an additional 75 item slots in your inventory!
            </p>
        </div>

        <div class="grid lg:grid-cols-3 gap-3 w-full md:w-2/3 m-auto">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i
                        class="ra ra-player text-primary-600 relative top-[10px] right-[10px]"
                    ></i>
                </x-slot>
                <x-slot:title>
                    <a
                        href="/information/monsters?table-filters[maps]=Delusional+Memories"
                    >
                        New Monsters To Fight
                    </a>
                </x-slot>

                <p>
                    Raid locations come with new monsters to fight that drop
                    quest items related to the Raid Quests!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i
                        class="ra ra-player-king text-primary-600 relative top-[10px] right-[10px]"
                    ></i>
                </x-slot>
                <x-slot:title>
                    <a
                        href="{{
                            route('info.page', [
                                'pageName' => 'delusional-silver',
                            ])
                        }}"
                    >
                        Gain Delusional Silver
                    </a>
                </x-slot>

                <p>
                    gain the next piece of your gear progression:
                    <a href="/information/delusional-silver">
                        Delusional Silver
                    </a>
                    . This gear comes with no enchantments allowing you to make
                    it your own!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i
                        class="ra ra-trail text-primary-600 relative top-[10px] right-[10px]"
                    ></i>
                </x-slot>
                <x-slot:title>
                    <a
                        href="{{
                            route('info.page', [
                                'pageName' => 'raids',
                            ])
                        }}"
                    >
                        Band together to take down the Corrupted Bishop
                    </a>
                </x-slot>

                <p>
                    Band together with other players and take down the raid
                    boss: The Corrupted Bishop
                </p>
            </x-core.cards.feature-card>
        </div>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <div
                class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"
            ></div>
        </div>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20 mb-20">
            <h2
                class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800"
            >
                <span class="fa-stack">
                    <i class="fas fa-shopping-bag"></i>
                </span>
                Gain and
                <a href="/information/ancestral-items">Ancestral Artifact</a>
                !
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                Gain a new powerful ancestral item specific to this raid! You
                only have to be the first player who kills the raid boss, to get
                such a powerful and unique item!
            </p>
        </div>

        <div class="w-full lg:w-2/4 mx-auto mt-10 lg:mt-20 mb-10 mt-4 lg:mt-0">
            <h2
                class="mb-5 font-thin text-center text-5xl dark:text-gray-300 text-gray-800"
            >
                <i class="far fa-question-circle"></i>
                FAQ
            </h2>
            <dl class="mt-3">
                <dt>How do I access the event?</dt>
                <dd>
                    Simply log in and head down to the Delusional Memories plane
                    and search for a skull location. One of these locations will
                    have the raid boss as the first critter in the list.
                </dd>
                <dt>What level should I be?</dt>
                <dd>
                    If you want to take on raid critters I would suggest you
                    <a href="/information/reincarnation">reincarnate</a>
                    your character at least once and level up to max leve again
                    as well as having a full set of
                    <a href="/information/twisted-earth">Twisted Earth</a>
                    gear. But if you are loking at fighting the raid boss, any
                    level will do!
                </dd>
            </dl>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const lightbox = GLightbox();
    </script>
@endpush
