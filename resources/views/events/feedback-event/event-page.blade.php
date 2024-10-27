@extends('layouts.app')


@section('content')
    <div class="container mx-auto lg:px-4 mb-5">
        <div class="text-center mb-10 lg:mt-10">
            <h3 class="mb-5 font-thin text-5xl dark:text-gray-300 text-gray-800 text-4xl md:text-7xl">Tlessa's Feedback event!</h3>
            <p class="mb-5 dark:text-gray-300 text-gray-800 italic">Help Tlessa gain invaluable feedback which helps us become the best PBBG around!</p>
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
                <i class="ra ra-feather-wing"></i>
                For two months, players new and old can come together to help Tlessa become the best!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                Gain valuable XP and Skill XP at a rate that will have you flying through levels in no time.
                Tlessa feedback event isn't just about feedback, its also about helping characters of all levels grow!
            </p>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                Players can see the survey results as well as The Creators response after the event has ended. You can see this in two places: The main page,
                there will be a survey section or when you are logged in and you open the left hand side bar. These survey results will
                stick around until the next event where we gather feedback.
            </p>
        </div>


        <div class="grid lg:grid-cols-3 gap-3 w-full md:w-2/3 m-auto my-10">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-muscle-fat text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a href="/information/character-xp">Battle XP Bonuses</a>
                </x-slot:title>

                <p>
                    Levels 1-1000 get 75 extra XP per kill. Above 1000, non-reincarnated players earn 250 more XP; reincarnated players get 500 more.
                </p>
            </x-core.cards.feature-card>

            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-player-dodge text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a href="{{ route('info.page', ['pageName' => 'skill-information']) }}">Training Skill XP Bonuses</a>
                </x-slot:title>

                <p>
                    Event participants gain 150 extra XP when training skills like Accuracy. Perfect for leveling up!
                </p>
            </x-core.cards.feature-card>

            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-flat-hammer text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a href="{{ route('info.page', ['pageName' => 'crafting']) }}">Crafting Skills XP Bonuses</a>
                </x-slot:title>

                <p>
                    Crafting skills earn 175 extra XP per successful craft or enchant. Double enchanting is highly rewarding!
                </p>
            </x-core.cards.feature-card>
        </div>


        <div class="text-center w-full lg:w-2/4 mx-auto my-10">
            <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">
                <i class="fas fa-sign"></i>
                Helps new players and Helps the game
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                This event is designed to draw a lot of players in who will play and take advantage of the increased XP as well as other events that run during this time like Currency Day
                where you get more currency from kills! Players who play for a combined minimum of 1 hour (does not have to be consecutive) will be asked to do a survey to give their feed back,
                upon completing said survey you will be rewarded with a shiny mythical item!
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
                    Just login and start killing things, crafting things and set a training skill to train!
                </dd>
                <dt>Can I use automation for this?</dt>
                <dd>
                    There is <a href="/information/exploration">Exploration</a> - which automates your fights, but crafting will have to be done manually.
                </dd>
                <dt>What rewards do I get?</dt>
                <dd>
                    You get a mythical item for completing the survey and helping Tlessa gain valuable feedback.
                </dd>
            </dl>
        </div>
    </div>
@endsection

