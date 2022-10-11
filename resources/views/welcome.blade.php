@extends('layouts.app')

@section('content')
    <div class="container mx-auto px-4 mb-5">
        <div class="text-center mb-10 mt-10">
            <h1 class="mb-5 font-thin text-7xl dark:text-gray-300 text-gray-800 text-4xl md:text-9xl">Planes of Tlessa</h1>
            <p class="mb-10 dark:text-gray-300 text-gray-800 italic">A world full of mystery and exploration.</p>
            <x-core.buttons.link-buttons.primary-button  css="mr-2" href="{{route('register')}}">
                Join Today!
            </x-core.buttons.link-buttons.primary-button>
            <x-core.buttons.link-buttons.success-button  css="mr-2" href="{{route('info.page', ['pageName' => 'home'])}}">
                Learn More
            </x-core.buttons.link-buttons.success-button>
            <x-core.buttons.link-buttons.success-button  css="mr-2" href="{{route('releases.list')}}">
                Releases
            </x-core.buttons.link-buttons.success-button>
        </div>
        <div class="flex items-center mr-2 justify-center mb-5 lg:hidden">
            @guest
                <label class="switch switch_outlined" data-toggle="tooltip" data-tippy-content="Toggle Dark Mode">
                    <input id="darkModeToggler" type="checkbox">
                    <span></span>
                </label>
                <span class="ml-4 mr-4 dark:text-white">Test Dark Mode</span>
                <x-core.buttons.link-buttons.login-button href="{{route('login')}}">
                    Login
                </x-core.buttons.link-buttons.login-button>
            @endguest

        </div>

        <img src="{{asset('promotion/game.png')}}" class="shadow rounded max-w-full h-auto align-middle border-none img-fluid max-w-[60%] my-4 m-auto"/>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <h2 class="mb-5 font-thin text-5xl dark:text-gray-300 text-gray-800">
                <i class="fas fa-globe-americas"></i>
                A World of Possibilities
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                Tlessa offers a lot for the average player to do, from crafting, enchanting, gear progression, quests, monsters, kingdoms to manage and to take. There is more to do here
                including but not limited to: Reach level 4000+, Fight epic Celestials, Get Faction Points for Uniques, Complete over 60 quests and so much more
            </p>

            <p class="mb-10 dark:text-gray-300 text-gray-800">
                <strong>Best part?</strong> it's all free! Just requires time investment, as little or as much as you want!
            </p>
        </div>

        <div class="grid lg:grid-cols-3 gap-3 w-full lg:w-2/3 m-auto">
            <x-core.cards.card-with-hover>
                <div class="flex justify-center items-center">
                    <div class="w-1/5 text-7xl">
                        <i class="ra ra-player text-primary-600 relative top-[10px] right-[10px]"></i>
                    </div>
                    <div class="w-4/5">
                        <h5>
                            <a href="{{route('info.page', [
                                'pageName' => 'equipment'
                            ])}}">Equip your character!</a>
                        </h5>
                        <p>
                            Buy/sell weapons, armor, rings and more to out fit your character
                            for the road ahead. Who knows what beasties you might find!
                        </p>
                    </div>
                </div>
            </x-core.cards.card-with-hover>
            <x-core.cards.card-with-hover>
                <div class="flex justify-center items-center">
                    <div class="w-1/5 text-7xl">
                        <i class="ra ra-player-king text-primary-600 relative top-[10px] right-[10px]"></i>
                    </div>
                    <div class="w-4/5">
                        <h5>
                            <a href="{{route('info.page', [
                                'pageName' => 'kingdoms'
                            ])}}">Rule Kingdoms!</a>
                        </h5>
                        <p>
                            Settle, Manage and wage war against other players! You can use Kingdom passives to train new skills to unlock new buildings
                            and units that give your kingdoms even more power!
                        </p>
                    </div>
                </div>
            </x-core.cards.card-with-hover>
            <x-core.cards.card-with-hover>
                <div class="flex justify-center items-center">
                    <div class="w-1/5 text-7xl">
                        <i class="ra ra-trail text-primary-600 relative top-[10px] right-[10px]"></i>
                    </div>
                    <div class="w-4/5">
                        <h5>
                            <a href="{{route('info.page', [
                                'pageName' => 'adventure',
                            ])}}">Go on adventures!</a>
                        </h5>
                        <p>
                            Travel to new locations and find out their mysteries by partaking in location based adventures!
                        </p>
                    </div>
                </div>
            </x-core.cards.card-with-hover>
        </div>

        <div class="grid md:grid-cols-2 gap-3 mt-5 w-full mt-10 mx-auto lg:w-2/3 md:mt-20">
            <img src="{{asset('promotion/map.png')}}" class="shadow rounded max-w-full h-auto align-middle border-none img-fluid w-100 mb-5" />
            <div class="md:flex md:items-center text-center md:text-left">
                <div>
                    <h2 class="mb-5 font-thin md:text-4xl lg:text-5xl dark:text-gray-300 text-gray-800">See where you're going!</h2>
                    <p class="mb-10 dark:text-gray-300 text-gray-800">Adventure on a map by clicking the action buttons. Set sail from one port to the other, traverse t other planes of existence!</p>

                    <x-core.buttons.link-buttons.primary-button href="{{route('info.page', [
                            'pageName' => 'movement',
                    ])}}">
                        Learn more
                    </x-core.buttons.link-buttons.primary-button>
                </div>
            </div>
        </div>

        <div class="text-center w-full lg:w-2/4 mx-auto mt-20">
            <h2 class="mb-5 font-thin text-5xl dark:text-gray-300 text-gray-800">
                <span class="fa-stack">
                            <i class="far fa-credit-card fa-stack-1x"></i>
                            <i class="fas fa-ban fa-stack-2x text-red-500"></i>
                        </span>
                No Cash Shops!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                This game is free. This game has one philosophy: You want it? Earn it! Every thing from the best gear,
                to the strongest kingdoms to ability to travel from one plane to the next is all only attainable by playing the game.
            </p>
        </div>

        <div class="grid lg:grid-cols-3 gap-3 mt-20 w-full lg:w-2/3 m-auto">
            <x-core.cards.card-with-hover>
                <div class="flex justify-center items-center">
                    <div class="w-1/5 text-7xl">
                        <i class="ra ra-anvil text-primary-600 relative top-[10px] right-[10px]"></i>
                    </div>
                    <div class="w-4/5">
                        <h5>
                            <a href="{{route('info.page', [
                                'pageName' => 'crafting',
                            ])}}">Crafting is simple</a>
                        </h5>
                        <p>No need to gather. You can just start crafting! Find tomes to get xp bonuses!</p>
                    </div>
                </div>
            </x-core.cards.card-with-hover>
            <x-core.cards.card-with-hover>
                <div class="flex justify-center items-center">
                    <div class="w-1/5 text-7xl">
                        <i class="ra ra-forging text-primary-600 relative top-[10px] right-[10px]"></i>
                    </div>
                    <div class="w-4/5">
                        <h5>
                            <a href="{{route('info.page', [
                                'pageName' => 'enchanting',
                            ])}}">Enchant Gear!</a>
                        </h5>
                        <p>With over 400 enchantments, there isn't anything you can't make for your character!</p>
                    </div>
                </div>
            </x-core.cards.card-with-hover>
            <x-core.cards.card-with-hover>
                <div class="flex justify-center items-center">
                    <div class="w-1/5 text-7xl">
                        <i class="ra ra-wooden-sign text-primary-600 relative top-[10px] right-[10px]"></i>
                    </div>
                    <div class="w-4/5">
                        <h5>
                            <a href="{{route('info.page', [
                                        'pageName' => 'market-board',
                                    ])}}">Market Board</a>
                        </h5>
                        <p>Buy and sell from the market board. Craft and Enchant items for others and make a profit!</p>
                    </div>
                </div>
            </x-core.cards.card-with-hover>
        </div>

        <div class="grid lg:grid-cols-3 gap-3 mt-2 w-full lg:w-2/3 m-auto">
            <x-core.cards.card-with-hover>
                <div class="flex justify-center items-center">
                    <div class="w-1/5 text-7xl">
                        <i class="ra ra-bone-bite text-primary-600 relative top-[10px] right-[10px]"></i>
                    </div>
                    <div class="w-4/5">
                        <h5>
                            <a href="{{route('info.page', [
                                'pageName' => 'player-vs-player',
                            ])}}">Pvp</a>
                        </h5>
                        <p>Fight other players for a chance to earn a Mythic Unique. Participate in monthly pvp tournaments.</p>
                    </div>
                </div>
            </x-core.cards.card-with-hover>
            <x-core.cards.card-with-hover>
                <div class="flex justify-center items-center">
                    <div class="w-1/5 text-7xl">
                        <i class="ra ra-compass text-primary-600 relative top-[10px] right-[10px]"></i>
                    </div>
                    <div class="w-4/5">
                        <h5>
                            <a href="{{route('info.page', [
                                        'pageName' => 'the-guide',
                                    ])}}">The Guide</a>
                        </h5>
                        <p>New Player? Enable the guide during registration to help you out and learn about the game!</p>
                    </div>
                </div>
            </x-core.cards.card-with-hover>
        </div>

        <div class="w-full lg:w-2/4 mx-auto mt-10 lg:mt-20 mb-10">
            <h2 class="mb-5 font-thin text-center text-5xl dark:text-gray-300 text-gray-800">
                <i class="far fa-question-circle"></i>
                FAQ
            </h2>
            <dl class="mt-3">
                <dt>Are there Adds?</dt>
                <dd>
                    No. There are no adds whatsoever.
                </dd>
                <dt>Is it persistent?</dt>
                <dd>
                    Yes. You can start an exploration, move your units from one kingdom to the next or attack another kingdom. log out and come back later and it all
                    ran for you behind the scenes. <strong>Kingdoms will never reset.</strong>
                </dd>
                <dt>Is it idle?</dt>
                <dd>
                   <a href="/information/exploration" class="dark:text-primary-300 dark:hover:text-primary-600">Exploration</a> is your best bet for automation.
                    It automates the whole fighting process so you can focus on other things like <a href="/information/crafting" class="dark:text-primary-300 dark:hover:text-primary-600">Crafting</a>
                    and <a href="/information/enchanting" class="dark:text-primary-300 dark:hover:text-primary-600">Enchanting</a> - one of the most vital aspects of Tlessa!
                </dd>
                <dt>Does it use energy systems?</dt>
                <dd>
                    No. Tlessa uses what's called: <a href="/information/time-gates" class="dark:text-primary-300 dark:hover:text-primary-600">Time Gates</a>. These apply to action you do and time you out
                    from doing that action again for a matter of seconds or minutes. However, the goal of Tlessa is
                    not to keep you engaged, so for example you could: Fight, Craft, Move and then wait for their respective timers
                    to end before doing the same thing. In the aforementioned example: Killing a monster gates you a 10-second time
                    out before being able to kill the monster again, but being killed by said monster, gives you a 20-second time out before being able
                    to revive.
                </dd>
                <dt>Are they're guilds?</dt>
                <dd>
                    No. In Tlessa, it's every person for themselves. There is no guild or clan system in Tlessa.
                </dd>
            </dl>
        </div>
    </div>
@endsection
