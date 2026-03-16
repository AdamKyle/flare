@extends('layouts.app')

@push('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css" />
    <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
@endpush

@section('content')
    <div class="container mx-auto lg:px-4 mb-5">
        <div class="text-center mb-10 lg:mt-10">
            <h3 class="mb-5 font-thin text-5xl dark:text-gray-300 text-gray-800 text-4xl md:text-7xl">The Smugglers are back!</h3>
            <p class="mb-5 dark:text-gray-300 text-gray-800 italic">Once Smugglers port was ruled by pirates until the merchants took over, now they have come back seeking retribution.</p>
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
            <div class="grid lg:grid-cols-3 gap-2 md:w-2/3 w-full mr-auto ml-auto">
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
                The Pirate Lord's son rises from Smugglers Port's bloody past
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                During this raid, players uncover the truth behind Smugglers Port's war, a forbidden love, and the false memories tied to the <a href="#">Delusional Memories</a> event.
                Fight through corrupted locations and work together to bring down the Pirate Lord's son.
            </p>

            <p class="mb-10 dark:text-gray-300 text-gray-800">
                <strong>Best part?</strong> it's all free! Just requires time investment, as little or as much as you want!
            </p>
        </div>


        <div class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 md:mt-20">
            <div class="mt-4 lg:mt-0">
                <img src="{{ asset('promotion/the-smugglers-are-back-raid/raid-locations.png') }}"
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
                Participate in two quest branches: one uncovers the truth behind Smugglers Port's war and the forbidden love that helped ignite it, while the other reveals the false memories surrounding your own past and your connection to The Creator.
                Defeat raid monsters to earn quest items, push through corrupted locations, and face the Pirate Lord's son at the heart of the raid.
            </p>
        </div>

        <div class="grid md:grid-cols-2 gap-6 mt-5 w-full mt-10 mx-auto lg:w-3/4 md:mt-20">
            <div class="md:flex md:items-center text-center md:text-left">
                <div>
                    <h2 class="mb-5 font-thin md:text-4xl lg:text-5xl dark:text-gray-300 text-gray-800">Put your skill to the test!</h2>
                    <p class="mb-10 dark:text-gray-300 text-gray-800">
                        <a href="/information/monsters?table-filters[maps]=Surface+Raid+Monsters">Raid Critters</a> are much stronger then you might be use to. While the <a href="/information/monsters?table-filters[maps]=Delusional+Memories+Raid+Bosses">Raid Boss</a> needs players to take it down, the raid critters will
                        put your gear to the test. Just take a look for your self.
                    </p>
                </div>
            </div>
            <div class="mt-4 lg:mt-0">
                <img src="{{ asset('promotion/the-smugglers-are-back-raid/son-of-priate-lords-fight.png') }}"
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
                Unlock <a href="/information/cosmetic-text" target="_blank">Cosmetic Text</a>
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                Complete the Raid Quests on Delusional Memories Plane to unlock Cosmetic Text and add a new visual customization option to your character.
            </p>
        </div>

        <div class="grid md:grid-cols-3 gap-3 w-full md:w-2/3 m-auto">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-player text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="/information/monsters?table-filters[maps]=Surface+Raid+Monsters">New Monsters To Fight</a>
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
                            'pageName' => 'pirate-lord-leather-set',
                        ]) }}">Pirate Lord leather</a>
                </x-slot:title>

                <p>
                    gain the next piece of your gear progression: Pirate Lord Leather. This gear comes with no enchantments allowing you to make it your own!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-trail text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="/information/monsters?table-filters[maps]=Surface+Raid+Bosses">Band together to take down the Sone of The Pirate Lords</a>
                </x-slot:title>

                <p>
                    Band together with other players and take down the raid boss: Sone of the Pirate Lords
                </p>
            </x-core.cards.feature-card>
        </div>

        <div class="w-full lg:w-2/4 mx-auto mt-10 lg:mt-20 mb-10 mt-4 lg:mt-0">
            <h2 class="mb-5 font-thin text-center text-5xl dark:text-gray-300 text-gray-800">
                <i class="far fa-question-circle"></i>
                FAQ
            </h2>
            <dl class="mt-3">
                <dt>How do I access the event?</dt>
                <dd>
                    Simply login and head to any of the corrupted locations. the Teleport under the map actions will will allow you to teleport to corrupted locations. From there you can also participate in the raid quests by being on surface.
                </dd>
                <dt>What level should I be?</dt>
                <dd>
                    Raid Bosses will allow any player of any level to attack them, the weaker you are the more they laugh at your attempts, but youll do some kind of damage. The more along you are, more mid game, the more damage youll do.
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
