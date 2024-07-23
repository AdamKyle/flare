@extends('layouts.app')


@section('content')
    <div class="container mx-auto lg:px-4 mb-5">
        <div class="text-center mb-10 lg:mt-10">
            <h3 class="mb-5 font-thin text-5xl dark:text-gray-300 text-gray-800 text-4xl md:text-7xl">Weekly Celestials!</h3>
            <p class="mb-5 dark:text-gray-300 text-gray-800 italic">A simple move in the wrong direction could unleash death upon the plane as the gates have been flung open!</p>
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

        <div class="text-center w-full lg:w-2/4 mx-auto my-20">
            <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">
                <i class="ra ra-broken-skull"></i>
                The gates have opened for 24 hours!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                When players move via directional movement, teleporting, setting sail or even traversing, there is a 80%
                chance that a celestial some where could spawn! Using /Pct can teleport you to that celestial to challenge such a fearsome beast!
            </p>
        </div>


        <div class="grid lg:grid-cols-3 gap-3 w-full md:w-2/3 m-auto my-10">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="fas fa-coins text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="/information/currencies">Earn Shards</a>
                </x-slot:title>

                <p>
                    Be the first to slaughter one of these beasts and get a godly reward in the form of currency rewards: Shards.
                    Useful in <a href="/information/alchemy">Alchemy</a>!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-monster-skull text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="{{ route('info.page', [
                            'pageName' => 'celestials',
                        ]) }}">Challenging creatures</a>
                </x-slot:title>

                <p>
                    Celestials are stronger then the creatures you are use to! gear is vital as these beasts will flee if you fail to kill it in one hit!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="fas fa-clock text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="{{ route('info.page', [
                            'pageName' => 'events',
                        ]) }}">24 hours only!</a>
                </x-slot:title>

                <p>
                    This event comes around once per week and gives players 24 hours to hunt down and slaughter celestials left right and center, simply by just moving!
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
                    Login, move around - use /PCT to travel to the celestial or /PC to find it's quaternaries.
                </dd>
                <dt>What level should I be?</dt>
                <dd>
                    It depends on which plane the Celestial Spawns on. For example - Shadow Plane Celestials are much stronger then Surface.
                    I would recommend being level 500 or higher with maxed out crafted gear, with maxed out enchantments before attempting these beasts.
                </dd>
            </dl>
        </div>
    </div>
@endsection

