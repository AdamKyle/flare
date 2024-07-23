@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
@endpush

@section('content')
    <div class="container mx-auto lg:px-4 mb-5">
        <div class="text-center mb-10 lg:mt-10">
            <h3 class="mb-5 font-thin text-5xl dark:text-gray-300 text-gray-800 text-4xl md:text-7xl">A mother trapped in her own pain</h3>
            <p class="mb-5 dark:text-gray-300 text-gray-800 italic">Her pain keeps her trapped in her own memories. Time to put down this grieving mother!</p>
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

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">
                <i class="ra ra-death-skull"></i>
                Icy Death Awaits!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                During this <a href="/information/raids">raid</a> players have a chance to participate in raid quests that flush out more of the story from <a href="/information/planes">The Ice Plane</a> event.
                Players can enter corrupted locations and put their might to the test as they work together to take down the raid boss!
            </p>

            <p class="mb-10 dark:text-gray-300 text-gray-800">
                <strong>Best part?</strong> it's all free! Just requires time investment, as little or as much as you want!
            </p>
        </div>


        <div class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 md:mt-20">
            <div class="mt-4 lg:mt-0">
                <img src="{{ asset('promotion/ice-queen-raid-event/ice-queen-raid-locations.png') }}"
                     class="shadow rounded max-w-full h-auto align-middle border-none img-fluid glightbox w-100 mb-5 cursor-pointer" />
                <div class="text-center text-sm">
                    Click to make larger.
                </div>
            </div>
            <div class="md:flex md:items-center text-center md:text-left">
                <div>
                    <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">Raid icons appear!
                    </h2>
                    <p class="mb-10 dark:text-gray-300 text-gray-800">During the raid, specific locations will become corrupted. This means the monsters are much stronger
                    and the reward is that much greater. One of these locations contains the raid boss that players work together to take down</p>
                </div>
            </div>
        </div>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">
                <span class="fa-stack">
                    <i class="ra ra-monster-skull mr-2"></i>
                </span>
                New <a href="/information/quests">Quests</a>!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
               Participate in two branches of quests! One will tell the story of The Jester and requires your ability to kill raid monsters to get the quest items,
                and the other quest line will lead to a cosmetic unlock. In this case Players get the ability to select a name tag that will show up when they use the public chat!
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 md:mt-20">
            <div class="md:flex md:items-center text-center md:text-left">
                <div>
                    <h2 class="mb-5 font-thin md:text-4xl lg:text-5xl dark:text-gray-300 text-gray-800">Put your skill to the test!</h2>
                    <p class="mb-10 dark:text-gray-300 text-gray-800">
                        <a href="/information/monsters?table-filters[maps]=The+Ice+Plane+Raid+Monsters#no-link">Raid Critters</a> are much stronger then you might be use to. While the <a href="/information/monsters?table-filters[maps]=The+Ice+Plane+Raid+Monsters">Raid Boss</a> needs players to take it down, the raid critters will
                        put your gear to the test. Just take a look for your self.
                    </p>
                </div>
            </div>
            <div class="mt-4 lg:mt-0">
                <img src="{{ asset('promotion/ice-queen-raid-event/ice-queen-attack.png') }}"
                     class="shadow rounded max-w-full h-auto align-middle border-none img-fluid glightbox w-100 mb-5 cursor-pointer" />
                <div class="text-center text-sm">
                    Click to make larger.
                </div>
            </div>

        </div>


        <div class="text-center w-full lg:w-2/4 mx-auto mt-20 mb-20">
            <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">
                <span class="fa-stack">
                    <i class="fas fa-shopping-bag"></i>
                </span>
                Gain and <a href="/information/ancestral-items">Ancestral Artifact</a>!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                Gain a new powerful ancestral item specific to this raid! You only have to be the first player who kills the raid boss,
                to get such a powerful and unique item!
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 my-20">
            <div class="mt-4 lg:mt-0">
                <img src="{{ asset('promotion/ice-queen-raid-event/name-tag.png') }}"
                     class="shadow rounded max-w-full h-auto align-middle border-none img-fluid glightbox w-100 mb-5 cursor-pointer" />
                <div class="text-center text-sm">
                    Click to make larger.
                </div>
            </div>
            <div class="md:flex md:items-center text-center md:text-left">
                <div>
                    <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">Gain the new Name Tag!
                    </h2>
                    <p class="mb-10 dark:text-gray-300 text-gray-800">You can select, from your settings, a name tag to appear when you
                        use the in game chat! You get this fro completing the last Quest from the raid quests when this raid is active!</p>
                </div>
            </div>
        </div>

        <div class="grid lg:grid-cols-3 gap-3 w-full md:w-2/3 m-auto">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-player text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="/information/monsters?table-filters[maps]=The+Ice+Plane+Raid+Monsters#no-link">New Monsters To Fight</a>
                </x-slot:title>

                <p>
                    Raid locations come with new monsters to fight that drop quest items related to the Raid Quests!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-player-king text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="{{ route('info.page', [
                            'pageName' => 'corrupted-ice',
                        ]) }}">Gain Corrupted Ice</a>
                </x-slot:title>

                <p>
                    gain the next piece of your gear progression: Corrupted Ice. This gear comes with no enchantments allowing you to make it your own!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-trail text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="{{ route('info.page', [
                            'pageName' => 'raids',
                        ]) }}">Band together to take down the Jester</a>
                </x-slot:title>

                <p>
                    Band together with other players and take down the raid boss: Jester of Time
                </p>
            </x-core.cards.feature-card>
        </div>

        <div class="w-full lg:w-2/4 mx-auto mt-10 lg:mt-20 mb-10 mt-4 lg:mt-0">
            <h2 class="mb-5 font-thin text-center text-5xl dark:text-gray-300 text-gray-800">
                <i class="far fa-question-circle"></i>
                FAQ
            </h2>Delusional Silver
            <dl class="mt-3">
                <dt>How do I access the event?</dt>
                <dd>
                    Simply log in and head down to the Ice Plane and search for a skull location. One of these locations will have the raid boss as the first critter in the list.
                </dd>
                <dt>What level should I be?</dt>
                <dd>
                    You should have reincarnated your character at least twice to make any meaningful damage as well as made use of a few class specialties.
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
