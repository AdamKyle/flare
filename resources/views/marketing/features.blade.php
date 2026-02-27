@extends('layouts.app')

@push('head')
  <link
    rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css"
  />
  <script src="https://cdn.jsdelivr.net/gh/mcstudios/glightbox/dist/js/glightbox.min.js"></script>
@endpush

@section('content')
<<<<<<< HEAD
  <div class="container mx-auto mb-5 px-4 pb-10">
    <div class="mb-10 text-center lg:mt-10">
      <h1
        class="mb-5 text-4xl text-7xl font-thin text-gray-800 md:text-9xl dark:text-gray-300"
      >
        Features
      </h1>
      <p class="mb-10 text-gray-800 italic dark:text-gray-300">
        All the features, none of the cost!
      </p>
=======
    <div class="container px-4 pb-10 mx-auto mb-5">
        <div class="mb-10 text-center lg:mt-10">
            <h1 class="mb-5 text-4xl font-thin text-gray-800 text-7xl dark:text-gray-300 md:text-9xl">Features</h1>
            <p class="mb-10 italic text-gray-800 dark:text-gray-300">All the features, none of the cost!</p>
        </div>

        <div>
            <img src="{{asset('promotion/character-sheet.png')}}" class="shadow rounded max-w-full h-auto align-middle border-none img-fluid lg:max-w-[60%] my-4 m-auto glightbox cursor-pointer"/>
            <div class="text-sm text-center">
                Click to make larger.
            </div>
        </div>

        <div class="w-full mx-auto mt-20 text-center lg:w-2/4">
            <h2 class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300">
                <i class="ra ra-monster-skull"></i>
                Character Development
            </h2>
            <p class="mb-10 text-gray-800 dark:text-gray-300">
                These features help you to develop your character in the world of Tlessa.
            </p>
        </div>

        <div class="grid w-full gap-3 m-auto lg:grid-cols-3 md:w-2/3">
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-player text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a href="{{route('info.page', [
                                'pageName' => 'races-and-classes'
                            ])}}">Various Races and Classes!</a>
                </x-slot:title>

                <p>
                    Choose a race for your character and choose a starting class! Races and classes when paired together
                    can give good bonuses towards stats!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-double-team text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a href="{{route('info.page', [
                                'pageName' => 'class-ranks'
                            ])}}">Switch Classes and Learn Special Abilities!</a>
                </x-slot:title>

                <p>
                    With class ranks, you can level other classes, learn their special abilities and mix and match. Some classes can
                    only be unlocked through the Class Rank system!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-trail text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a href="{{route('info.page', [
                        'pageName' => 'reincarnation',
                    ])}}">Reincarnation</a>
                </x-slot:title>

                <p>
                   Reincarnate your character to set their level back to one, but keep all the skills and stats. Make your self powerful
                   as you re-level and gain more stats! Reincarnate multipletimes to gain more and more power!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-player-pyromaniac text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a href="{{route('info.page', [
                        'pageName' => 'skill-information',
                    ])}}">Level Skills</a>
                </x-slot:title>

                <p>
                   Level your character skills to get better at Attacking, Dodgeing, and even unleash special attacks on monsters!
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-aware text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a href="{{route('info.page', [
                        'pageName' => 'class-skills',
                    ])}}">Class Skills</a>
                </x-slot:title>

                <p>
                   Every class in the game has a special skill, which when leveled will unleash a special attack.
                </p>
            </x-core.cards.feature-card>
            <x-core.cards.feature-card>
                <x-slot:icon>
                    <i class="ra ra-axe text-primary-600 relative top-[10px] right-[10px]"></i>
                </x-slot:icon>
                <x-slot:title>
                    <a href="{{route('info.page', [
                        'pageName' => 'equipment',
                    ])}}">Equipment</a>
                </x-slot:title>

                <p>
                   There is avariety if weapons, armours, rings, spells and so on that you can craft, buy, earn and find through
                   various events, battling, raiding and questing! Outfit your character today!
                </p>
            </x-core.cards.feature-card>
        </div>
        <div class="my-4">
            <div class="mt-10">
                <img src="{{asset('promotion/quests-map.png')}}" class="shadow rounded max-w-full h-auto align-middle border-none img-fluid lg:max-w-[60%] my-4 m-auto glightbox cursor-pointer"/>
                <div class="text-sm text-center">
                    Click to make larger.
                </div>
            </div>

            <div class="w-full mx-auto mt-20 text-center lg:w-2/4">
                <h2 class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300">
                    <i class="ra ra-footprint"></i>
                    World of Exploration
                </h2>
                <p class="mb-10 text-gray-800 dark:text-gray-300">
                    These are the features that help you explore the world
                </p>
            </div>

            <div class="grid w-full gap-3 m-auto lg:grid-cols-3 md:w-2/3">
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="fas fa-sign text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'quests'
                                ])}}">Quests</a>
                    </x-slot:title>

                    <p>
                        Quests allow you to progress your character futrther and unlock features gated behind the quest system.
                        You can also use quests to unlock the various planes!
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-player-lift text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'planes'
                                ])}}">Planes</a>
                    </x-slot:title>

                    <p>
                        Traverse from the Surface world to the various other planes and fight new and fearsome monsters! Advance your character
                        and the story with the various quests on each plane
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="fas fa-dungeon text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'races-and-classes'
                                ])}}">Locations</a>
                    </x-slot:title>

                    <p>
                        Visit tons of locations for quest items, fight harder monsters for specific quest items and drops!
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-batwings text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'monsters'
                                ])}}">Monsters</a>
                    </x-slot:title>

                    <p>
                        Fight monsters to find magical items, gain exp and currencies! Some locations have harder monsters, some planes
                        while weakening you will buff the monster.
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-desert-skull text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'celestials'
                                ])}}">Celestials</a>
                    </x-slot:title>

                    <p>
                        Monsters stronger then the ones that roam the land! You can conjure them and they have a specific
                        times when they spawn more easily for players to hunt for valuable shards!
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-death-skull text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'raids'
                                ])}}">Raids</a>
                    </x-slot:title>

                    <p>
                        Specific events will corrupt locations on one plane causing a new list of super strong creatures and a special
                        one for all players to try and take down together: Raid Bosses! Players gain epic loot and new Gear
                        peices you cant find anywhere!
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-arrow-cluster text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'factions'
                                ])}}">Factions</a>
                    </x-slot:title>

                    <p>
                        Players can earn powerful items simply by killing creatures on the Plane they are currently on.
                        Earning faction points will gain you notoriety with the plane you are on!
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-double-team text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'faction-loyalt'
                                ])}}">Faction Loyalty</a>
                    </x-slot:title>

                    <p>
                        Once a player maxes out their Faction with a plane, they can then Pledge and assist an NPC
                        with their Bounty and Crafting tasks.

                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="fas fa-calendar text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'events'
                                ])}}">Events</a>
                    </x-slot:title>

                    <p>
                        Players can participate in various types of events, either weekly, monthly or special events like raids or
                        The Winter Event.
                    </p>
                </x-core.cards.feature-card>
            </div>
        </div>
        <div class="my-4">
            <div class="mt-10">
                <img src="{{asset('promotion/fighting.png')}}" class="shadow rounded max-w-full h-auto align-middle border-none img-fluid lg:max-w-[60%] my-4 m-auto glightbox cursor-pointer"/>
                <div class="text-sm text-center">
                    Click to make larger.
                </div>
            </div>

            <div class="w-full mx-auto mt-20 text-center lg:w-2/4">
                <h2 class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300">
                    <i class="ra ra-muscle-up"></i>
                    Character Progression For Days!
                </h2>
                <p class="mb-10 text-gray-800 dark:text-gray-300">
                    Grow your character through various means!
                </p>
            </div>

            <div class="grid w-full gap-3 m-auto lg:grid-cols-3 md:w-2/3">
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-anvil text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'crafting'
                                ])}}">Crafting</a>
                    </x-slot:title>

                    <p>
                        Players can craft items well beyond what they can purchase in the shop for even more power.
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-burning-embers text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'enchanting'
                                ])}}">Enchanting</a>
                    </x-slot:title>

                    <p>
                        Enchant items to give your gear even more powers, boost your stats, deal damage and weaken your enemy
                        all while trapping tehm in your web of magics!
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra  ra-burning-book text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'random-enchants'
                                ])}}">Uniques</a>
                    </x-slot:title>

                    <p>
                        Earn Uniques through Factions, but also purchase them from the Queen of Hearts. These are more powerful then the best enchantment,
                        and only get better the more gold spend!
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra  ra-crystal-wand text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'mythical-items'
                                ])}}">Mythics</a>
                    </x-slot:title>

                    <p>
                       One of the most powerful of enchantments is the Mythic! Delve deep into the depths of Purgatory Dungeons or even participate and weekly fights
                       to have a chance to win one of these!
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-potion text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'alchemy'
                                ])}}">Alchemy</a>
                    </x-slot:title>

                    <p>
                        Craft usable items that you can drop on your enemies kingdoms or even use on  your self to make
                        your self even stronger for a limited time!
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-ball text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'gems'
                                ])}}">Gem Crafting/Sockets</a>
                    </x-slot:title>

                    <p>
                        Craft gems to then apply to gear you assign sockets to in order to increase your Elemental Atonement
                        for Raids!
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-fire-shield text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'trinketry'
                                ])}}">Trinketry</a>
                    </x-slot:title>

                    <p>
                        The deeper you delve, the harder they hit. Protect your self child from ambushes and counters!
                        help your self to counter and ambush the enemy! Crafting trinkets will help with this!
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-ankh text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'holy-items'
                                ])}}">Holy Items</a>
                    </x-slot:title>

                    <p>
                        Using the power of alchemy, you can create and then apply Holy Oils which help increase your damage,
                        healing and other aspects of your character!
                    </p>
                </x-core.cards.feature-card>
                <x-core.cards.feature-card>
                    <x-slot:icon>
                        <i class="ra ra-crowned-heart text-primary-600 relative top-[10px] right-[10px]"></i>
                    </x-slot:icon>
                    <x-slot:title>
                        <a href="{{route('info.page', [
                                    'pageName' => 'ancestral-items'
                                ])}}">Ancestral Items</a>
                    </x-slot:title>

                    <p>
                        Particpta ein raids and kill the Raid Boss to earn your self the most powerful of legendary items! Ancestral items come with
                        their own skill tree that con be leveled over time to give your character even more power!
                    </p>
                </x-core.cards.feature-card>
            </div>
        </div>
        <div class="my-4">
            <div class="mt-10">
                <img src="{{asset('promotion/guide-quest.png')}}" class="shadow rounded max-w-full h-auto align-middle border-none img-fluid lg:max-w-[60%] my-4 m-auto glightbox cursor-pointer"/>
                <div class="text-sm text-center">
                    Click to make larger.
                </div>
            </div>

            <div class="w-full mx-auto mt-20 text-center lg:w-2/4">
                <h2 class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300">
                    <i class="ra ra-wooden-sign"></i>
                    And more features then you can count!
                </h2>
                <p class="mb-10 text-gray-800 dark:text-gray-300">
                    Endless possiblilities, hours of fun, nothing but time, not even money can make you better, stronger or more prepared
                    for the stronger challenges ahead.
                </p>
            </div>
        </div>
>>>>>>> master
    </div>

    <div>
      <img
        src="{{ asset('promotion/character-sheet.png') }}"
        class="img-fluid glightbox m-auto my-4 h-auto max-w-full cursor-pointer rounded border-none align-middle shadow lg:max-w-[60%]"
      />
      <div class="text-center text-sm">Click to make larger.</div>
    </div>

    <div class="mx-auto mt-20 w-full text-center lg:w-2/4">
      <h2
        class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300"
      >
        <i class="ra ra-monster-skull"></i>
        Character Development
      </h2>
      <p class="mb-10 text-gray-800 dark:text-gray-300">
        These features help you to develop your character in the world of
        Tlessa.
      </p>
    </div>

    <div class="m-auto grid w-full gap-3 md:w-2/3 lg:grid-cols-3">
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
                'pageName' => 'races-and-classes',
              ])
            }}"
          >
            Various Races and Classes!
          </a>
        </x-slot>

        <p>
          Choose a race for your character and choose a starting class! Races
          and classes when paired together can give good bonuses towards stats!
        </p>
      </x-core.cards.feature-card>
      <x-core.cards.feature-card>
        <x-slot:icon>
          <i
            class="ra ra-double-team text-primary-600 relative top-[10px] right-[10px]"
          ></i>
        </x-slot>
        <x-slot:title>
          <a
            href="{{
              route('info.page', [
                'pageName' => 'class-ranks',
              ])
            }}"
          >
            Switch Classes and Learn Special Abilities!
          </a>
        </x-slot>

        <p>
          With class ranks, you can level other classes, learn their special
          abilities and mix and match. Some classes can only be unlocked through
          the Class Rank system!
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
                'pageName' => 'reincarnation',
              ])
            }}"
          >
            Reincarnation
          </a>
        </x-slot>

        <p>
          Reincarnate your character to set their level back to one, but keep
          all the skills and stats. Make your self powerful as you re-level and
          gain more stats! Reincarnate multipletimes to gain more and more
          power!
        </p>
      </x-core.cards.feature-card>
      <x-core.cards.feature-card>
        <x-slot:icon>
          <i
            class="ra ra-player-pyromaniac text-primary-600 relative top-[10px] right-[10px]"
          ></i>
        </x-slot>
        <x-slot:title>
          <a
            href="{{
              route('info.page', [
                'pageName' => 'skill-information',
              ])
            }}"
          >
            Level Skills
          </a>
        </x-slot>

        <p>
          Level your character skills to get better at Attacking, Dodgeing, and
          even unleash special attacks on monsters!
        </p>
      </x-core.cards.feature-card>
      <x-core.cards.feature-card>
        <x-slot:icon>
          <i
            class="ra ra-aware text-primary-600 relative top-[10px] right-[10px]"
          ></i>
        </x-slot>
        <x-slot:title>
          <a
            href="{{
              route('info.page', [
                'pageName' => 'class-skills',
              ])
            }}"
          >
            Class Skills
          </a>
        </x-slot>

        <p>
          Every class in the game has a special skill, which when leveled will
          unleash a special attack.
        </p>
      </x-core.cards.feature-card>
      <x-core.cards.feature-card>
        <x-slot:icon>
          <i
            class="ra ra-axe text-primary-600 relative top-[10px] right-[10px]"
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
            Equipment
          </a>
        </x-slot>

        <p>
          There is avariety if weapons, armours, rings, spells and so on that
          you can craft, buy, earn and find through various events, battling,
          raiding and questing! Outfit your character today!
        </p>
      </x-core.cards.feature-card>
    </div>
    <div class="my-4">
      <div class="mt-10">
        <img
          src="{{ asset('promotion/quests-map.png') }}"
          class="img-fluid glightbox m-auto my-4 h-auto max-w-full cursor-pointer rounded border-none align-middle shadow lg:max-w-[60%]"
        />
        <div class="text-center text-sm">Click to make larger.</div>
      </div>

      <div class="mx-auto mt-20 w-full text-center lg:w-2/4">
        <h2
          class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300"
        >
          <i class="ra ra-footprint"></i>
          World of Exploration
        </h2>
        <p class="mb-10 text-gray-800 dark:text-gray-300">
          These are the features that help you explore the world
        </p>
      </div>

      <div class="m-auto grid w-full gap-3 md:w-2/3 lg:grid-cols-3">
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="fas fa-sign text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'quests',
                ])
              }}"
            >
              Quests
            </a>
          </x-slot>

          <p>
            Quests allow you to progress your character futrther and unlock
            features gated behind the quest system. You can also use quests to
            unlock the various planes!
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-player-lift text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'planes',
                ])
              }}"
            >
              Planes
            </a>
          </x-slot>

          <p>
            Traverse from the Surface world to the various other planes and
            fight new and fearsome monsters! Advance your character and the
            story with the various quests on each plane
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="fas fa-dungeon text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'races-and-classes',
                ])
              }}"
            >
              Locations
            </a>
          </x-slot>

          <p>
            Visit tons of locations for quest items, fight harder monsters for
            specific quest items and drops!
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-batwings text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'monsters',
                ])
              }}"
            >
              Monsters
            </a>
          </x-slot>

          <p>
            Fight monsters to find magical items, gain exp and currencies! Some
            locations have harder monsters, some planes while weakening you will
            buff the monster.
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-desert-skull text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'celestials',
                ])
              }}"
            >
              Celestials
            </a>
          </x-slot>

          <p>
            Monsters stronger then the ones that roam the land! You can conjure
            them and they have a specific times when they spawn more easily for
            players to hunt for valuable shards!
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-death-skull text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'raids',
                ])
              }}"
            >
              Raids
            </a>
          </x-slot>

          <p>
            Specific events will corrupt locations on one plane causing a new
            list of super strong creatures and a special one for all players to
            try and take down together: Raid Bosses! Players gain epic loot and
            new Gear peices you cant find anywhere!
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-arrow-cluster text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'factions',
                ])
              }}"
            >
              Factions
            </a>
          </x-slot>

          <p>
            Players can earn powerful items simply by killing creatures on the
            Plane they are currently on. Earning faction points will gain you
            notoriety with the plane you are on!
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-double-team text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'faction-loyalt',
                ])
              }}"
            >
              Faction Loyalty
            </a>
          </x-slot>

          <p>
            Once a player maxes out their Faction with a plane, they can then
            Pledge and assist an NPC with their Bounty and Crafting tasks.
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
            Players can participate in various types of events, either weekly,
            monthly or special events like raids or The Winter Event.
          </p>
        </x-core.cards.feature-card>
      </div>
    </div>
    <div class="my-4">
      <div class="mt-10">
        <img
          src="{{ asset('promotion/fighting.png') }}"
          class="img-fluid glightbox m-auto my-4 h-auto max-w-full cursor-pointer rounded border-none align-middle shadow lg:max-w-[60%]"
        />
        <div class="text-center text-sm">Click to make larger.</div>
      </div>

      <div class="mx-auto mt-20 w-full text-center lg:w-2/4">
        <h2
          class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300"
        >
          <i class="ra ra-muscle-up"></i>
          Character Progression For Days!
        </h2>
        <p class="mb-10 text-gray-800 dark:text-gray-300">
          Grow your character through various means!
        </p>
      </div>

      <div class="m-auto grid w-full gap-3 md:w-2/3 lg:grid-cols-3">
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
              Crafting
            </a>
          </x-slot>

          <p>
            Players can craft items well beyond what they can purchase in the
            shop for even more power.
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-burning-embers text-primary-600 relative top-[10px] right-[10px]"
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
              Enchanting
            </a>
          </x-slot>

          <p>
            Enchant items to give your gear even more powers, boost your stats,
            deal damage and weaken your enemy all while trapping tehm in your
            web of magics!
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-burning-book text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'random-enchants',
                ])
              }}"
            >
              Uniques
            </a>
          </x-slot>

          <p>
            Earn Uniques through Factions, but also purchase them from the Queen
            of Hearts. These are more powerful then the best enchantment, and
            only get better the more gold spend!
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-crystal-wand text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'mythical-items',
                ])
              }}"
            >
              Mythics
            </a>
          </x-slot>

          <p>
            One of the most powerful of enchantments is the Mythic! Dwelve deep
            into the depths of Purgatory Dungeons or even participate and weekly
            fights to have a chance to win one of these!
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-potion text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'alchemy',
                ])
              }}"
            >
              Alchemy
            </a>
          </x-slot>

          <p>
            Craft usable items that you can drop on your enemies kingdoms or
            even use on your self to make your self even stronger for a limited
            time!
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-ball text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'gems',
                ])
              }}"
            >
              Gem Crafting/Sockets
            </a>
          </x-slot>

          <p>
            Craft gems to then apply to gear you assign sockets to in order to
            increase your Elemental Atonement for Raids!
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-fire-shield text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'trinketry',
                ])
              }}"
            >
              Trinketry
            </a>
          </x-slot>

          <p>
            The deeper you dwelve, the harder they hit. Protect your self child
            from ambushes and counters! help your self to counter and ambush the
            enemy! Crafting trinkets will help with this!
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-ankh text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'holy-items',
                ])
              }}"
            >
              Holy Items
            </a>
          </x-slot>

          <p>
            Using the power of alchemy, you can create and then apply Holy Oils
            which help increase your damage, healing and other aspects of your
            character!
          </p>
        </x-core.cards.feature-card>
        <x-core.cards.feature-card>
          <x-slot:icon>
            <i
              class="ra ra-crowned-heart text-primary-600 relative top-[10px] right-[10px]"
            ></i>
          </x-slot>
          <x-slot:title>
            <a
              href="{{
                route('info.page', [
                  'pageName' => 'ancestral-items',
                ])
              }}"
            >
              Ancestral Items
            </a>
          </x-slot>

          <p>
            Particpta ein raids and kill the Raid Boss to earn your self the
            most powerful of legendary items! Ancestral items come with their
            own skill tree that con be leveled over time to give your character
            even more power!
          </p>
        </x-core.cards.feature-card>
      </div>
    </div>
    <div class="my-4">
      <div class="mt-10">
        <img
          src="{{ asset('promotion/guide-quest.png') }}"
          class="img-fluid glightbox m-auto my-4 h-auto max-w-full cursor-pointer rounded border-none align-middle shadow lg:max-w-[60%]"
        />
        <div class="text-center text-sm">Click to make larger.</div>
      </div>

      <div class="mx-auto mt-20 w-full text-center lg:w-2/4">
        <h2
          class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300"
        >
          <i class="ra ra-wooden-sign"></i>
          And more features then you can count!
        </h2>
        <p class="mb-10 text-gray-800 dark:text-gray-300">
          Endless possiblilities, hours of fun, nothing but time, not even money
          can make you better, stronger or more prepared for the stronger
          challenges ahead.
        </p>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    const lightbox = GLightbox();
  </script>
@endpush
