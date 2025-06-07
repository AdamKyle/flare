@extends('layouts.app')

@push('head')
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css"
    />
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
@endpush

@section('content')
    <div class="container mx-auto mb-5 lg:px-4">
        <div class="mb-10 text-center lg:mt-10">
            <h1
                class="mb-5 text-4xl font-thin text-gray-800 text-7xl dark:text-gray-300 md:text-9xl"
            >
                Planes of Tlessa
            </h1>
            <p class="mb-10 italic text-gray-800 dark:text-gray-300">
                A world full of mystery and exploration.
            </p>
            <div class="flex justify-center w-full gap-2 ml-auto mr-auto">
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
                    Learn More
                </x-core.buttons.link-buttons.success-button>
                <x-core.buttons.link-buttons.orange-button
                    css="mr-2"
                    href="{{ route('releases.list') }}"
                >
                    Release Notes
                </x-core.buttons.link-buttons.orange-button>
                <x-core.buttons.link-buttons.orange-button
                    css="mr-2"
                    href="{{ route('game.whos-playing') }}"
                >
                    Who's Playing?
                </x-core.buttons.link-buttons.orange-button>
            </div>
        </div>
        <div class="grid w-2/3 gap-2 mb-5 ml-auto mr-auto md:hidden">
            @guest
                <div class="flex items-center justify-center mb-4 mr-2">
                    <label
                        class="switch switch_outlined"
                        data-toggle="tooltip"
                        data-tippy-content="Toggle Dark Mode"
                    >
                        <input id="darkModeToggler" type="checkbox" />
                        <span></span>
                    </label>
                    <span class="ml-4 mr-4 dark:text-white">
                        Test Dark Mode
                    </span>
                </div>
                <x-core.buttons.link-buttons.login-button
                    href="{{ route('login') }}"
                >
                    Login
                </x-core.buttons.link-buttons.login-button>
            @endguest
        </div>

        <div>
            <img
                src="{{ asset('promotion/game.png') }}"
                class="shadow rounded max-w-full h-auto align-middle border-none img-fluid lg:max-w-[60%] my-4 m-auto glightbox cursor-pointer"
            />
            <div class="text-sm text-center">Click to make larger.</div>
        </div>

        <div class="w-full mx-auto mt-20 text-center lg:w-2/4">
            <h2
                class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300"
            >
                <i class="fas fa-globe-americas"></i>
                A World of Possibilities
            </h2>
            <p class="mb-10 text-gray-800 dark:text-gray-300">
                Tlessa offers a lot for the average player to do, from crafting,
                enchanting, gear progression, quests, monsters, kingdoms to
                manage and to take. There is more to do here including but not
                limited to: Reach level 4000+, Fight epic Celestials, Get
                Faction Points for Uniques, Complete over 60 quests and so much
                more.
            </p>

            <p class="mb-10 text-gray-800 dark:text-gray-300">
                <strong>Best part?</strong>
                it's all free! Just requires time investment, as little or as
                much as you want!
            </p>
            <div
                class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"
            ></div>
        </div>

        @if (count($scheduledEventsRunning) > 0)
            @include(
                './welcome-partials/event-container',
                [
                    'scheduledEventsRunning' => $scheduledEventsRunning,
                ]
            )
        @endif

        <div class="w-full mx-auto mt-20 text-center lg:w-2/4">
            <h2
                class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300"
            >
                <i class="fas fa-calendar"></i>
                Tlessa's Events
            </h2>
            <p class="mb-10 text-gray-800 dark:text-gray-300">
                Tlessa has a lot of events that keep players engaged! You can
                check out our event calendar and see what events are coming up.
                All event all intended for all players regardless of level or
                power to participate in!
            </p>

            <p class="mb-10 text-gray-800 dark:text-gray-300">
                <x-core.buttons.link-buttons.success-button
                    href="{{route('event.calendar')}}"
                >
                    View the upcoming events!
                </x-core.buttons.link-buttons.success-button>
            </p>
            <div
                class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"
            ></div>
        </div>

        @if (SurveyStats::canShowSurveyMenuOption())
            <div class="w-full mx-auto mt-20 text-center lg:w-2/4">
                <h2
                    class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300"
                >
                    <i class="fas fa-poll"></i>
                    The latest survey has been released!
                </h2>
                <p class="mb-10 text-gray-800 dark:text-gray-300">
                    Tlessa will occasionally hold an event where players can get
                    large amounts of XP to help them progress. After 6 hours of
                    total game play, does not need to be consecutive, players
                    are promoted with a survey - filling out rhe survey helps
                    Tlessa make the game better and become of the best PBBGS
                    around! Players also get a shiny mythical item for
                    completing them!
                </p>

                <p class="mb-10 text-gray-800 dark:text-gray-300">
                    <x-core.buttons.link-buttons.success-button
                        href="{{route('survey.stats')}}"
                    >
                        View the survey results!
                    </x-core.buttons.link-buttons.success-button>
                </p>
            </div>
        @endif

        <div class="grid w-full gap-3 m-auto lg:grid-cols-3 md:w-2/3 mt-20">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i
                        class="ra ra-player text-primary-600 relative top-[10px] right-[10px]"
                    ></i>
                </x-slot>
                <x-slot:title>
                    <a
                        href="{{
                            route('info.page', [
                                'pageName' => 'equipment',
                            ])
                        }}"
                    >
                        Equip your character!
                    </a>
                </x-slot>

                <p>
                    Buy/sell weapons, armor, rings and more to out fit your
                    character for the road ahead. Who knows what beasties you
                    might find!
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
                                'pageName' => 'kingdoms',
                            ])
                        }}"
                    >
                        Rule Kingdoms!
                    </a>
                </x-slot>

                <p>
                    Settle, Manage and wage war against other players! You can
                    use Kingdom passives to train new skills to unlock new
                    buildings and units that give your kingdoms even more power!
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
                                'pageName' => 'exploration',
                            ])
                        }}"
                    >
                        Automated exploration
                    </a>
                </x-slot>

                <p>
                    Set up automation and let the game do all the fighting for
                    you! Come back later or do other actions and get rewards
                    over time!
                </p>
            </x-core.cards.feature-card>
        </div>

        <div
            class="grid w-full gap-6 mx-auto mt-5 mt-10 md:grid-cols-2 lg:w-3/4 md:mt-20"
        >
            <div class="mt-4 lg:mt-0">
                <img
                    src="{{ asset('promotion/map.png') }}"
                    class="h-auto max-w-full mb-5 align-middle border-none rounded shadow cursor-pointer img-fluid glightbox w-100"
                />
                <div class="text-sm text-center">Click to make larger.</div>
            </div>
            <div class="text-center md:flex md:items-center md:text-left">
                <div>
                    <h2
                        class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300"
                    >
                        See where you're going!
                    </h2>
                    <p class="mb-10 text-gray-800 dark:text-gray-300">
                        Adventure on a map by clicking the action buttons. Set
                        sail from one port to the other, traverse to other
                        planes of existence!
                    </p>

                    <x-core.buttons.link-buttons.primary-button
                        href="{{ route('info.page', [
                            'pageName' => 'movement',
                        ]) }}"
                    >
                        Learn more
                    </x-core.buttons.link-buttons.primary-button>
                </div>
            </div>
        </div>

        <div class="w-full mx-auto mt-20 text-center lg:w-2/4">
            <h2
                class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300"
            >
                <span class="fa-stack">
                    <i class="far fa-credit-card fa-stack-1x"></i>
                    <i class="text-red-500 fas fa-ban fa-stack-2x"></i>
                </span>
                No Cash Shops!
            </h2>
            <p class="mb-10 text-gray-800 dark:text-gray-300">
                This game is free. This game has one philosophy: You want it?
                Earn it! Every thing from the best gear, to the strongest
                kingdoms to ability to travel from one plane to the next is all
                only attainable by playing the game.
            </p>
        </div>

        <div
            class="grid w-full gap-6 mx-auto mt-5 mt-10 md:grid-cols-2 lg:w-3/4 md:mt-20"
        >
            <div class="mt-4 lg:mt-0">
                <img
                    src="{{ asset('promotion/corrupted-locations.png') }}"
                    class="h-auto max-w-full mb-5 align-middle border-none rounded shadow cursor-pointer img-fluid glightbox w-100"
                />
                <div class="text-sm text-center">Click to make larger.</div>
            </div>

            <div class="text-center md:flex md:items-center md:text-left">
                <div>
                    <h2
                        class="mb-5 font-thin text-gray-800 md:text-4xl lg:text-5xl dark:text-gray-300"
                    >
                        Raids
                    </h2>
                    <p class="mb-10 text-gray-800 dark:text-gray-300">
                        Join together with other players to take down fearsom
                        creatures that corrupte locations on the map and win
                        epic rewards for being the first to slay the beast!
                    </p>

                    <p class="mb-10 text-gray-800 dark:text-gray-300">
                        Raids are
                        <a href="/information/events">scheduled</a>
                        events that last for over a month and give players a
                        chance to be the one who wins the
                        <a href="/information/ancestral-items">
                            Ancestral Item
                        </a>
                        the fiend drops! Raid Bosses will respawn one hour after
                        death.
                    </p>

                    <x-core.buttons.link-buttons.primary-button
                        href="{{ route('info.page', [
                            'pageName' => 'raids',
                        ]) }}"
                    >
                        Learn more
                    </x-core.buttons.link-buttons.primary-button>
                </div>
            </div>
        </div>

        <div class="w-full mx-auto mt-20 text-center lg:w-2/4">
            <h2
                class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300"
            >
                <i class="mr-2 fas fa-mouse-pointer"></i>
                Some Clicking Required!
            </h2>
            <p class="mb-10 text-gray-800 dark:text-gray-300">
                Tlessa is not an idle game. We do offer
                <a href="/information/exploration">Exploration</a>
                to make the progression a bit easier, however, players should be
                prepared to not put the game on autopilot and walk away.
                <a href="/information/some-clicking-required">Learn more</a>
                ,
            </p>
        </div>

        <div class="grid w-full gap-3 m-auto mt-20 lg:grid-cols-3 md:w-2/3">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i
                        class="ra ra-anvil text-primary-600 relative top-[10px] right-[10px]"
                    ></i>
                </x-slot>
                <x-slot:title>
                    <a
                        href="{{
                            route('info.page', [
                                'pageName' => 'crafting',
                            ])
                        }}"
                    >
                        Crafting is simple
                    </a>
                </x-slot>

                <p>
                    No need to gather. You can just start crafting! Find tomes
                    to get xp bonuses!
                </p>
            </x-core.cards.feature-card>

            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i
                        class="ra ra-forging text-primary-600 relative top-[10px] right-[10px]"
                    ></i>
                </x-slot>
                <x-slot:title>
                    <a
                        href="{{
                            route('info.page', [
                                'pageName' => 'enchanting',
                            ])
                        }}"
                    >
                        Enchant Gear!
                    </a>
                </x-slot>

                <p>
                    With over 400 enchantments, there isn't anything you can't
                    make for your character!
                </p>
            </x-core.cards.feature-card>

            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i
                        class="ra ra-wooden-sign text-primary-600 relative top-[10px] right-[10px]"
                    ></i>
                </x-slot>
                <x-slot:title>
                    <a
                        href="{{
                            route('info.page', [
                                'pageName' => 'market-board',
                            ])
                        }}"
                    >
                        Market Board
                    </a>
                </x-slot>

                <p>
                    Buy and sell from the market board. Craft and Enchant items
                    for others and make a profit!
                </p>
            </x-core.cards.feature-card>
        </div>

        <div class="grid w-full gap-3 m-auto mt-2 lg:grid-cols-3 md:w-2/3">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i
                        class="ra ra-bone-bite text-primary-600 relative top-[10px] right-[10px]"
                    ></i>
                </x-slot>
                <x-slot:title>
                    <a
                        href="{{
                            route('info.page', [
                                'pageName' => 'weekly-fights',
                            ])
                        }}"
                    >
                        Weekely Fights
                    </a>
                </x-slot>

                <p>
                    Participate in Weekly Fights to unlock new content, earn
                    epic items such as Mythics and Legandry items as well as
                    Cosmic Items during specific events!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i
                        class="ra ra-compass text-primary-600 relative top-[10px] right-[10px]"
                    ></i>
                </x-slot>
                <x-slot:title>
                    <a
                        href="{{
                            route('info.page', [
                                'pageName' => 'the-guide',
                            ])
                        }}"
                    >
                        The Guide
                    </a>
                </x-slot>

                <p>
                    New Player? The Guide will be auto enabled for you to help
                    you navigate the world of Tlessa!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i
                        class="fas fa-calendar text-primary-600 relative top-[10px] right-[10px]"
                    ></i>
                </x-slot>
                <x-slot:title>
                    <a
                        href="{{
                            route('info.page', [
                                'pageName' => 'events',
                            ])
                        }}"
                    >
                        Events
                    </a>
                </x-slot>

                <p>
                    Tlessa offers events that reward players for participating!
                </p>
            </x-core.cards.feature-card>
        </div>

        <div class="w-full m-auto mt-4 mb-8 text-center md:w-2/3">
            <h4
                class="mb-5 text-3xl font-thin text-gray-800 dark:text-gray-300"
            >
                And so many more!
            </h4>
            <p class="mb-10 italic text-gray-800 dark:text-gray-300">
                Planes of tlessa has so many rich and diverse features its hard
                to showcase them all!
            </p>
            <div
                class="flex justify-center w-full ml-auto mr-auto text-center lg:w-1/3"
            >
                <x-core.buttons.link-buttons.success-button
                    css="mr-2 mb-4"
                    href="{{ route('game.features') }}"
                >
                    See all the features
                </x-core.buttons.link-buttons.success-button>
                <x-core.buttons.link-buttons.orange-button
                    css="mr-2 mb-4"
                    href="{{ route('game.whos-playing') }}"
                >
                    See who's playing Tlessa!
                </x-core.buttons.link-buttons.orange-button>
            </div>
        </div>

        <div class="w-full mx-auto mt-4 mt-10 mb-10 lg:w-2/4 lg:mt-20 lg:mt-0">
            <h2
                class="mb-5 text-5xl font-thin text-center text-gray-800 dark:text-gray-300"
            >
                <i class="far fa-question-circle"></i>
                FAQ
            </h2>
            <dl class="mt-3">
                <dt>Are there Ads?</dt>
                <dd>No. There are no ads whatsoever.</dd>
                <dt>Is it persistent?</dt>
                <dd>
                    Yes. You can start an exploration, move your units from one
                    kingdom to the next or attack another kingdom. log out and
                    come back later and it all ran for you behind the scenes.
                    <strong>Kingdoms will never reset.</strong>
                </dd>
                <dt>Is it idle?</dt>
                <dd>
                    <a
                        href="/information/exploration"
                        class="dark:text-primary-300 dark:hover:text-primary-600"
                    >
                        Exploration
                    </a>
                    is your best bet for automation. It automates the whole
                    fighting process so you can focus on other things like
                    <a
                        href="/information/crafting"
                        class="dark:text-primary-300 dark:hover:text-primary-600"
                    >
                        Crafting
                    </a>
                    and
                    <a
                        href="/information/enchanting"
                        class="dark:text-primary-300 dark:hover:text-primary-600"
                    >
                        Enchanting
                    </a>
                    - one of the most vital aspects of Tlessa!
                </dd>
                <dt>Does it use energy systems?</dt>
                <dd>
                    No. Tlessa uses what's called:
                    <a
                        href="/information/time-gates"
                        class="dark:text-primary-300 dark:hover:text-primary-600"
                    >
                        Time Gates
                    </a>
                    . These apply to action you do and time you out from doing
                    that action again for a matter of seconds or minutes.
                    However, the goal of Tlessa is not to keep you engaged, so
                    for example you could: Fight, Craft, Move and then wait for
                    their respective timers to end before doing the same thing.
                    In the aforementioned example: Killing a monster gates you a
                    10-second time out before being able to kill the monster
                    again, but being killed by said monster, gives you a
                    20-second time out before being able to revive.
                </dd>
                <dt>Are they're guilds?</dt>
                <dd>
                    No. In Tlessa, it's every person for themselves. There is no
                    guild or clan system in Tlessa.
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
