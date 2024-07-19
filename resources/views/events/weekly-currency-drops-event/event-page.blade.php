


@extends('layouts.app')


@section('content')
    <div class="container mx-auto lg:px-4 mb-5">
        <div class="text-center mb-10 lg:mt-10">
            <h3 class="mb-5 font-thin text-5xl dark:text-gray-300 text-gray-800 text-4xl md:text-7xl">Weekly Currency Drops!</h3>
            <p class="mb-5 dark:text-gray-300 text-gray-800 italic">Slaughter the creatures before you and live in a wealth of currencies child! Alchemy awaits!</p>
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
                <i class="ra ra-crystals"></i>
                Gold Dust and Shards Rain for 24 Hours!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                When you log in and kill any creature - other than a celestial, you gain 1 - 500 Gold Dust and Shards!
                These can be used in Alchemy as well as other Mid to late game systems that depend on such a rare resource!
            </p>
        </div>


        <div class="grid md:grid-cols-3 gap-3 w-full md:w-2/3 m-auto my-10">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="fas fa-coins text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="/information/currencies">Earn Shards</a>
                </x-slot:title>

                <p>
                    Shards are used in a variety of things, how ever late game you need them more for <a href="/information/alchemy">Alchemy</a>, which can grant you
                    powerful boosts to your stats as well as bring your enemies kingdoms to their knees!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra  ra-crystals text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="{{ route('info.page', [
                            'pageName' => 'currencies',
                        ]) }}">Gold Dust</a>
                </x-slot:title>

                <p>
                    Gold Dust is gained in a small amount in a variety of ways. From <a href="/information/quests">Quests</a>, to <a href="/information/alchemy">Alchemy</a>, to late game activities, players need this vital resource.
                    Today you can gain more then you will ever need!
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
                    This event comes around once per week and gives players 24 hours to gain as much Gold Dust and Shards as possible!
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
                    Login and kill a non celestial creature! That's it. Your server messages section will show you how much Gold Dust and Shards you gain.
                </dd>
                <dt>Does this stack with special locations that also drop these currencies?</dt>
                <dd>
                    Yes it does!
                </dd>
            </dl>
        </div>
    </div>
@endsection
