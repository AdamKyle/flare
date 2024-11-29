@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
@endpush

@section('content')
    <div class="container mx-auto lg:px-4 mb-5">
        <div class="mb-10 lg:mt-10">
            <div class="text-center">
                <h3 class="mb-5 font-thin text-5xl dark:text-gray-300 text-gray-800 text-4xl md:text-7xl">Delusional Memory Event</h3>
                <p class="mb-5 dark:text-gray-300 text-gray-800 italic">A plane with a story of living deep with in ones own delusions.</p>
            </div>
            @if (!is_null($event))
                <p class="mb-10 text-orange-600 dark:text-orange-300 my-2 text-center">
                    <strong>Runs from</strong>: {{$event->start_date->format('l, j \of F, Y \a\t g:iA')}}
                    <strong>until</strong>: {{$event->end_date->format('l, j \of F, Y \a\t g:iA')}}
                </p>
            @else
                <div class="w-1/3 mx-auto">
                <x-core.alerts.info-alert title="Not yet scheduled">
                    This event hasn't been scheduled yet. Don't worry The Creator will schedule it soon! Below you can learn more about it for when it is scheduled!
                </x-core.alerts.info-alert>
                </div>
            @endif
            <div class="grid md:grid-cols-3 gap-2 md:w-2/3 w-full mr-auto ml-auto text-center">
                <x-core.buttons.link-buttons.primary-button css="mr-2" href="{{ route('register') }}">
                    Join Today!
                </x-core.buttons.link-buttons.primary-button>
                <x-core.buttons.link-buttons.success-button css="mr-2"
                                                            href="{{ route('info.page', ['pageName' => 'home']) }}">
                    Learn More (About the game)
                </x-core.buttons.link-buttons.success-button>
                <x-core.buttons.link-buttons.orange-button css="mr-2" href="{{ route('releases.list') }}">
                    Game Release Notes
                </x-core.buttons.link-buttons.orange-button>
            </div>
        </div>

        <div>
            <img src="{{ asset('promotion/delusional-memories-event/delusional-event.png') }}"
                 class="shadow rounded max-w-full h-auto align-middle border-none img-fluid lg:max-w-[60%] my-4 m-auto glightbox cursor-pointer" />
            <div class="text-sm text-center">
                Click to make larger.
            </div>
        </div>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">
                <i class="fas fa-globe-americas"></i>
                A temporary place to explore full of riches and wonders
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                During the Delusional Memories event, players of all levels and equipment can venture down - through traversing - to the new
                plane where they can participate in global events to earn mid game gear to help your progression through Tlessa!
            </p>

            <p class="mb-10 dark:text-gray-300 text-gray-800">
                <strong>Best part?</strong> it's all free! Just requires time investment, as little or as much as you want!
            </p>
        </div>


        <div class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 md:mt-20">
            <div class="mt-4 lg:mt-0">
                <img src="{{ asset('promotion/delusional-memories-event/delusional-memories-map.png') }}"
                     class="shadow rounded max-w-full h-auto align-middle border-none img-fluid glightbox w-100 mb-5 cursor-pointer" />
                <div class="text-center text-sm">
                    Click to make larger.
                </div>
            </div>
            <div class="md:flex md:items-center text-center md:text-left">
                <div>
                    <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">New Map
                    </h2>
                    <p class="mb-10 dark:text-gray-300 text-gray-800">Adventure on a new map and explore new locations! There are new places to settle in this
                        land controlled by The Federation and The Corrupted Church! What could the secrets of a Jester hide in this land?</p>
                </div>
            </div>
        </div>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">
                <span class="fa-stack">
                    <i class="ra ra-monster-skull mr-2"></i>
                </span>
                Monsters scale to your ability
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
               Returning player who has Access to end game content like Purgatory? Prepare your self for a challenge as the monsters down here are much stronger
                then what you might use to! new player who wants to participate? Do not worry your monster list will be that of basic monsters allowing you to participate
                in all aspects!
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 md:mt-20">
            <div class="md:flex md:items-center text-center md:text-left">
                <div>
                    <h2 class="mb-5 font-thin md:text-4xl lg:text-5xl dark:text-gray-300 text-gray-800">Global Events!</h2>
                    <p class="mb-10 dark:text-gray-300 text-gray-800">
                        Join together with other players and complete phases of global events, be they Battling, Crafting or Enchanting!
                        Players who participate can be rewarded with mythical items for the battle phase and Legendary Uniques for
                        participating in the crafting and enchanting phase.
                    </p>
                </div>
            </div>
            <div class="mt-4 lg:mt-0">
                <img src="{{ asset('promotion/delusional-memories-event/delusional-memories-events.png') }}"
                     class="shadow rounded max-w-full h-auto align-middle border-none img-fluid glightbox w-100 mb-5 cursor-pointer" />
                <div class="text-center text-sm">
                    Click to make larger.
                </div>
            </div>

        </div>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">
                <i class="fas fa-mouse-pointer mr-2"></i>
                New <a href="/information/quests">Quests</a> and new story!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                With this event we dive into the delusional machinations of a mad Jester ever on the search for his brother.
                His own delusional fantasies play out before you! What's real and whats not?!
            </p>
        </div>


        <div class="grid lg:grid-cols-3 gap-3 w-full md:w-2/3 m-auto">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-player text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="/information/monsters?table-filters[maps]=Delusional+Memories">New Monsters To Fight</a>
                </x-slot:title>

                <p>
                    If you have access to <a href="/information/planes">Purgatory</a>, fight powerful new monsters that test your might and courage! New? You will be able to fight easier creatures so you can participate to!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-trail text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="/game-event-info?event_type=jester-of-time-raid">Jester of Time Raid</a>
                </x-slot:title>

                <p>
                    Fight the Jester of Time and his minions for a chance at wining the new tier of gear: <a href="/information/delusional-silver">Delusional Silver</a>! All players regardless of level can participate in the raid.
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-player-king text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="/game-event-info?event_type=corrupted-bishop-raid">Corrupted Bishop Raid</a>
                </x-slot:title>

                <p>
                    Fight the Corrupted Bishop and his minions for a chance at wining the new tier of gear: <a href="/information/delusional-silver">Delusional Silver</a>! All players regardless of level can participate in the raid.
                </p>
            </x-core.cards.feature-card>
        </div>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <div class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 md:mt-20">
            <div class="mt-4 lg:mt-0">
                <img src="{{ asset('promotion/delusional-memories-event/cosmic-item.png') }}"
                     class="shadow rounded max-w-full h-auto align-middle border-none img-fluid glightbox w-100 mb-5 cursor-pointer" />
                <div class="text-center text-sm">
                    Click to make larger.
                </div>
            </div>
            <div class="md:flex md:items-center text-center md:text-left">
                <div>
                    <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">Cosmic Items
                    </h2>
                    <p class="mb-10 dark:text-gray-300 text-gray-800">Powerful new enchanted items, known as <a href="/information/cosmic-items">Cosmic Gear</a>, can drop at the Alchemical Church <a href="/information/locations">location</a> while on this map. Players just have to omplete the new quests to unlock the ability to take on powerful new <a href="/information/weekly-fights">Weekly Fights</a> down here and eaern them selves a chance at getting this gear!</p>
                </div>
            </div>
        </div>

        <div class="w-full lg:w-2/4 mx-auto mt-10 lg:mt-20 mb-10 mt-4 lg:mt-0">
            <h2 class="mb-5 font-thin text-center text-5xl dark:text-gray-300 text-gray-800">
                <i class="far fa-question-circle"></i>
                FAQ
            </h2>
            <dl class="mt-3">
                <dt>How do I access the event?</dt>
                <dd>
                    Simply log in or create a new character and once you are in, click <a href="/information/traverse">Traverse</a> under the map. If you are on mobile, select Map Movement
                    from the drop down of actions. From here - under the map - is a traverse button, click that to then select Delusional Memories and enter the
                    event.
                </dd>
                <dt>What happens when the event is over?</dt>
                <dd>
                    When the event ends players wil be moved to Surface - the starting map. All kingdoms settled on The Ice Plane will fall to the ground and
                    the player with the most kingdoms gets a full set of end game gear: <a href="/information/delusional-silver">Delusional Silver</a>.
                </dd>
                <dt>Unique and Mythical End Game Gear - Seems Easy!</dt>
                <dd>
                    Yes and no. While all players can participate in the event goals and earn these pieces of gear, the gear will only take you so far as, you will need to level your character and take advantage of
                    a variety of other systems in game in order to take full advantage of the gear.
                </dd>
                <dt>Lots of this sounds like high level content, what can new players do?</dt>
                <dd>
                    New players can join in on the quests and the new story line, they can also participate in the global events. As stated before those with out access to end game content, will
                    face early game monsters to make it fair and inviting to all. Players, even new players, can get <a href="/information/unique-items">Unique</a> and even a <a href="/information/mythical-items">Mythical</a> <a href="/information/delusional-silver">Delusional Silver</a>.
                    gear down here by participating in the global events, this will take them a long way until they understand more systems in the game.
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
