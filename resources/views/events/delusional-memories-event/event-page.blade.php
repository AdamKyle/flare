@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
@endpush

@section('content')
    <div class="container mx-auto lg:px-4 mb-5">
        <div class="text-center mb-10 lg:mt-10">
            <h3 class="mb-5 font-thin text-5xl dark:text-gray-300 text-gray-800 text-4xl md:text-7xl">Delusional Memory Event</h3>
            <p class="mb-5 dark:text-gray-300 text-gray-800 italic">A plane with a story of living deep with in ones own delusions.</p>
            <p class="mb-10 text-orange-600 dark:text-orange-300 my-2">
                <strong>Runs from</strong>: {{$event->start_date->format('l, j \of F, Y \a\t g:iA')}}
                <strong>until</strong>: {{$event->end_date->format('l, j \of F, Y \a\t g:iA')}}
            </p>
            <div class="grid md:grid-cols-3 gap-2 md:w-2/3 w-full mr-auto ml-auto">
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
                    <i class="far fa-credit-card fa-stack-1x"></i>
                    <i class="fas fa-ban fa-stack-2x text-red-500"></i>
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
                Some Clicking Required!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                Tlessa is not an idle game. We do offer <a href="/information/exploration">Exploration</a> to make the
                progression a bit easier,
                however, players should be prepared to not put the game on autopilot and walk away. <a
                    href="/information/some-clicking-required">Learn more</a>,
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 md:mt-20">
            <div class="mt-4 lg:mt-0">
                <img src="{{ asset('promotion/delusional-memories-event/delusional-memories-quests.png') }}"
                     class="shadow rounded max-w-full h-auto align-middle border-none img-fluid glightbox w-100 mb-5 cursor-pointer" />
                <div class="text-center text-sm">
                    Click to make larger.
                </div>
            </div>
            <div class="md:flex md:items-center text-center md:text-left">
                <div>
                    <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">New Quests
                    </h2>
                    <p class="mb-10 dark:text-gray-300 text-gray-800">If you are a new player or a returning player, there are a whole set of new
                        quests that can be done down here to give the world more life and progress the story. Players no matter how new,
                        or veteran can participate. the stories are done such that you don't need the context to participate!</p>
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
                    Simply log in or create a new character and once you are in, click Traverse under the map. If you are on mobile, select Map Movement
                    from the drop down of actions. From here - under the map - is a traverse button, click that to then select Delusional Memories and enter the
                    event.
                </dd>
                <dt>What happens when the event is over?</dt>
                <dd>
                    When the event ends players wil be moved to Surface - the starting map. All kingdoms settled on Delusional Memories will fall to the ground and
                    the player with the most kingdoms gets a full set of end game gear: Delusional Silver.
                </dd>
                <dt>Mythical, Legendary and End Game Gear - Seems Easy!</dt>
                <dd>
                    Yes and no. While all players can participate in the event goals and earn these pieces of gear, the gear will only take you so far as, you will need to level your character and take advantage of
                    a variety of other systems in game in order to take full advantage of the gear.
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
