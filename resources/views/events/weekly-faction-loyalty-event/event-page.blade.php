@extends('layouts.app')


@section('content')
    <div class="container mx-auto lg:px-4 mb-5">
        <div class="text-center mb-10 lg:mt-10">
            <h3 class="mb-5 font-thin text-5xl dark:text-gray-300 text-gray-800 text-4xl md:text-7xl">Weekly Faction Loyalty!</h3>
            <p class="mb-5 dark:text-gray-300 text-gray-800 italic">Help Npc's to increase their fame. The more fame, the better protected your kingdoms are!</p>
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
                Faction Loyalty Requirements are Halved for 24 hours!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                Helping Npc's with their tasks to gain fame helps to protect kingdoms from item damage, especially the more NPC's on the same plane you help.
                Alas their requirements can be tedious, you have to click. Well today, when you level an NPC's fame level, their next levels requirements are halved! Making the process that much faster!
            </p>
        </div>


        <div class="grid lg:grid-cols-3 gap-3 w-full md:w-2/3 m-auto my-10">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-hand text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="/information/faction-loyalty">Pledge your allegiance</a>
                </x-slot:title>

                <p>
                    Pledge to the plane. Help the NPC, gain Fame with them and in return they use their magics to protect your kingdoms!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-queen-crown text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a
                        href="{{ route('info.page', [
                            'pageName' => 'factions',
                        ]) }}">Factions Unlock Loyalty</a>
                </x-slot:title>

                <p>
                    Unfamiliar with Factions? Learn more about a system that allows you to gain Medium and Legendary Uniques from
                    simply killing creatures!
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

        <div class="text-center w-full lg:w-2/4 mx-auto my-10">
            <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">
                <i class="fas fa-sign"></i>
                Needed for <a href="/information/quests">Quests</a>!
            </h2>
            <p class="mb-10 dark:text-gray-300 text-gray-800">
                As you delve deeper into the story of Tlessa, you will encounter quests that require you to do NPC Faction Loyalty Tasks inorder to progress and unlock more features.
                I would suggest you take full advantage of this day!
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
                    You need to have one plane's Faction to level 5. You can see this on your character sheet. From here you can pledge to that Plane from the Faction section.
                    At this stage you will see a new tab in your action section on the Game Tab, from here you can assist NPC's with their Bounty and Crafting Tasks!
                </dd>
                <dt>Can I use automation on the bounty tasks?</dt>
                <dd>
                    No, they must be done manually.
                </dd>
                <dt>What rewards do I get?</dt>
                <dd>
                    You get XP, Gold, Items and Item Defence on your kingdoms which protects your kingdoms from players dropping items
                    on them to do damage, how ever - this only applies if you are pledged to that plane.
                </dd>
            </dl>
        </div>
    </div>
@endsection

