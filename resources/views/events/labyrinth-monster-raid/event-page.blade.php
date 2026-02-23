@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
@endpush

@section('content')
    <div class="container mx-auto lg:px-4 mb-5">
        <div class="mb-10 lg:mt-10">
            <div class="text-center">
                <h3 class="mb-5 font-thin text-5xl dark:text-gray-300 text-gray-800 text-4xl md:text-7xl">The Labyrinth Monster</h3>
                <p class="mb-5 dark:text-gray-300 text-gray-800 italic">A little girl and a witches curse, whats the real story here?</p>
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
            <div class="text-center grid md:grid-cols-3 gap-2 md:w-2/3 w-full mr-auto ml-auto">
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
                The Little Girl is hunted by her parents ...
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                During this <a href="/information/raids">raid</a> players have a chance to participate in raid quests that flush out more of the story of The Little Girl and how The Witch placed a curse on her parents. All is not what it seems though ...
                Players can enter corrupted locations and put their might to the test as they work together to take down the raid boss!
            </p>

            <p class="mb-10 dark:text-gray-300 text-gray-800">
                <strong>Best part?</strong> it's all free! Just requires time investment, as little or as much as you want!
            </p>
        </div>


        <div class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 md:mt-20">
            <div class="mt-4 lg:mt-0">
                <img src="{{ asset('promotion/labyrinth-monster-raid/raid-location.png') }}"
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
                and the other quest line will lead to a cosmetic unlock. In this case Players get 75 additional slots for their inventory, brining their max inventory to 150! This counts for Alchemy, Gems and regular inventory space.
        </div>

        <div class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 md:mt-20">
            <div class="md:flex md:items-center text-center md:text-left">
                <div>
                    <h2 class="mb-5 font-thin md:text-4xl lg:text-5xl dark:text-gray-300 text-gray-800">Put your skill to the test!</h2>
                    <p class="mb-4 dark:text-gray-300 text-gray-800">
                        <a href="/information/monsters?table-filters[maps]=Delusional+Memories+Raid+Monsters#no-link">Raid Critters</a> are much stronger then you might be use to. While the <a href="/information/monsters?table-filters[maps]=Labyrinth+Raid+Monsters">Raid Boss</a> needs players to take it down, the raid critters will
                        put your gear to the test. Just take a look for your self.
                    </p>
                    <p class="mb-10 dark:text-gray-300 text-gray-800"><strong>Are you new?</strong> Don't fret, you can even participate in the Raid Boss fight, while the raid critters might be
                    too strong for you, the raid boss can be participated in by all players of all skill and level. High end players are limited in their damage output to make things fun and balanced!</p>
                </div>
            </div>
            <div class="mt-4 lg:mt-0">
                <img src="{{ asset('promotion/labyrinth-monster-raid/labyrinth-monster-raid.png') }}"
                     class="shadow rounded max-w-full h-auto align-middle border-none img-fluid glightbox w-100 mb-5 cursor-pointer" />
                <div class="text-center text-sm">
                    Click to make larger.
                </div>
            </div>

        </div>


        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">
                <span class="fa-stack">
                    <i class="fas fa-shopping-bag"></i>
                </span>
                Increase Inventory Max to 150
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                Complete the raid quests and earn an additional 75 inventory slots! this will allow you to carry
                more weapons, armour, rings, spells, alchemy items and or even gems!
            </p>
        </div>

        <div class="grid lg:grid-cols-3 gap-3 w-full md:w-2/3 m-auto">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-player text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="/information/monsters?table-filters[maps]=Labyrinth+Raid+Monsters">New Monsters To Fight</a>
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
                            'pageName' => 'labyrinth-cloth',
                        ]) }}">Gain Labyrinth Cloth</a>
                </x-slot:title>

                <p>
                    Earn a full set of Labyrinth Cloth gear set. This will come unenchanted and allow you to customize it to your builds need.
                    You have to be the one who kills the raid boss to earn this full set.
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
                        ]) }}">Band together to take down the monster</a>
                </x-slot:title>

                <p>
                    Band together with other players and take down the raid boss: The Enraged Little Girl
                </p>
            </x-core.cards.feature-card>
        </div>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <div class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
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

        <div class="w-full lg:w-2/4 mx-auto mt-10 lg:mt-20 mb-10 mt-4 lg:mt-0">
            <h2 class="mb-5 font-thin text-center text-5xl dark:text-gray-300 text-gray-800">
                <i class="far fa-question-circle"></i>
                FAQ
            </h2>
            <dl class="mt-3">
                <dt>How do I access the event?</dt>
                <dd>
                    Simply log in and head down to the Labyrinth plane and search for a skull location. One of these locations will have the raid boss as the first critter in the list.
                </dd>
                <dt>What level should I be?</dt>
                <dd>
                    It is suggested that you have level to at least level 5,000 to participate in raid quests and the raid boss fight. it is recommended you have reincarnated a few times. New players can still participate
                    and be rewarded with the +75 Inventory Max, if they have at least level past level 1,000
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
