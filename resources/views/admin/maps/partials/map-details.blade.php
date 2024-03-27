<x-core.layout.info-container>
    @php
        $backUrl = route('maps');

        if (!auth()->user()) {
            $backUrl = '/information/planes';
        } elseif (
            !auth()
                ->user()
                ->hasRole('Admin')
        ) {
            $backUrl = '/information/planes';
        }
    @endphp

    <x-core.cards.card-with-title title="{{ $map->name }}" buttons="true" backUrl="{{ $backUrl }}"
        editUrl="{{ route('map.bonuses', [$map->id]) }}">
        <div class="grid md:grid-cols-2 gap-2">
            <div>
                <img src="{{ $mapUrl }}" width="500"
                    class="shadow rounded max-w-full h-auto align-middle border-none img-fluid" />
            </div>
            <div>
                <h3 class="my-4">Map Bonuses</h3>
                <dl>
                    <dt>XP Bonus</dt>
                    <dd>{{ is_null($map->xp_bonus) ? 0 : $map->xp_bonus * 100 }}%</dd>
                    <dt>Skill XP Bonus</dt>
                    <dd>{{ is_null($map->skill_training_bonus) ? 0 : $map->skill_training_bonus * 100 }}%</dd>
                    <dt>Drop Chance Bonus</dt>
                    <dd>{{ is_null($map->drop_chance_bonus) ? 0 : $map->drop_chance_bonus * 100 }}%</dd>
                    <dt>Enemy Stat Increase</dt>
                    <dd>{{ is_null($map->enemy_stat_bonus) ? 0 : $map->enemy_stat_bonus * 100 }}%</dd>
                    <dt>Character Damage Deduction:</dt>
                    <dd>{{ !is_null($map->character_attack_reduction) ? $map->character_attack_reduction * 100 . '%' : '0%' }}
                    </dd>
                    @if (!is_null($map->required_location_id))
                        <dt>Must be at location (X/Y):</dt>
                        <dd>{{ $map->requiredLocation->x }}/{{ $map->requiredLocation->y }}</dd>
                        <dt>On Plane:</dt>
                        <dd>{{ $map->requiredLocation->map->name }}</dd>
                    @endif
                </dl>
                <p class="my-4">
                    These bonuses will apply to Exploration as well.
                </p>
                @if ($map->mapType()->isShadowPlane())
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                    <h3 class="my-4">Tips</h3>
                    <p>
                        Shadow Planes reduces your stats and increases the enemies stats. You will also notice that in
                        addition to
                        you becoming weaker and the enemy becoming stronger, there are plane bonuses to XP, Skill XP and
                        Drop Chance
                        here which stacks with your looting skill.
                    </p>
                @endif

                @if ($map->mapType()->isHell())
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                    <h3 class="my-4">Caution</h3>
                    <p>
                        Hell is very similar to Shadow Planes, except now we reduce your stats and increase the enemies
                        by a bit more!
                    </p>
                    <p class="my-2">
                        With out a <a href="/information/quest-items">Dead Kings Crown</a> your enchantments down here
                        that do damage, will be reduced to those that only do non stacking damage.
                    </p>
                    <p class="my-2">
                        You will want to make sure you have Stat Reducing Affixes and Entrancing <a
                            href="/information/enchanting">affixes</a> down here.
                    </p>
                    <p class="my-2">
                        Further complicating things, vampires life stealing percentage is reduced by 10% down here.
                        For example 99% life stealing, becomes ~89% life stealing.
                        Casters without high resistance reduction and skill reduction gear
                        will find their spells are being evaded. Quest items which make your affixes irresistible no
                        longer work down here.
                    </p>
                @endif

                @if ($map->mapType()->isPurgatory())
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                    <x-core.alerts.info-alert title="ATTN!">
                        You need to be a location in Hell to access this place. See above to see the location you need
                        to be at.
                        Once there, the Traverse Section will show Purgatory as an option.
                    </x-core.alerts.info-alert>
                    <h3 class="my-4">Caution</h3>
                    <p>
                        Enemies are the strongest down here. Without a <a href="/information/quest-items">Dead Kings
                            Crown</a>
                        your enchantments down here that do damage, will be reduced to those that only do non stacking
                        damage.
                    </p>
                    <p class="my-2">
                        Players will also notice a new mechanic down here <a href="/information/combat">Ambush and
                            Counter</a>. Monsters can ambush you and counter your attacks.
                        Players are suggested to start slowly, gaining <a href="/information/currencies">Copper
                            Coins</a> which they can use along side <a href="/information/currencies">Gold Dust</a>
                        to train a new crafting skill: <a href="/information/trinketry">Trinketry</a>. These new items
                        have Ambush and Counter Chance/Resistance which is
                        vital for further down the list as well as for PVp (optional).
                    </p>
                    <p class="my-2">
                        Further complicating things, vampires life stealing percentage is reduced by 20% down here.
                        For example 99% life stealing, becomes ~79% life stealing.
                        Casters without high resistance reduction and skill reduction gear
                        will find their spells are being evaded. Quest items which make your affixes irresistible no
                        longer work down here.
                    </p>
                    <p class="my-2">
                        Finally, Enemies down here have Higher stats, even the beginning enemies. Players will want to
                        invest in <a href="/information/holy-items">Holy Items</a> to further boost their damage
                        as well as use appropriate affixes to reduce enemy stats and entrance them.
                    </p>
                @endif

                @if ($map->mapType()->isTwistedMemories())
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                    <x-core.alerts.info-alert title="ATTN!">
                        Players will not traverse as normal, read on below. You will complete three quest lines to gain a new item and go to a specific location
                        to be automatically traversed to the plane.
                    </x-core.alerts.info-alert>
                    <h3 class="my-4">Twisted Memories corrupt the land</h3>
                    <p>
                        Welcome to Twisted Memories plane, an end game map where creatures are much more difficult and give out more XP.
                    </p>
                    <p class="my-2">
                       Players will need to complete a series of quests to get here. The quest line starts in Dungeons with: Looking for a prince
                        and ends with a quest chain in Hell called: Love is dangerous.
                    </p>
                    <p class="my-2">
                        From here players just need to head to the Twisted Dimensional Gate and be automatically traversed to the new plane.
                    </p>
                    <p class="my-2">
                        Players can settle kingdoms, but the land mass down here is so small that you might find your self defending your lands more often then not.
                    </p>
                    <p class="my-2">
                        Players will find a new set of quests here that tell the story of the plane and it's inhabitants, which
                        carries on from the above required quests for entry.
                    </p>
                    <p>
                        Vampires have their life stealing reduced by 25% down here. For example dealing 99% life stealing
                        damage will now be: ~74% life stealing damage.
                    </p>
                @endif
                @if (!is_null($itemNeeded))
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                    <h3 class="my-4">Item required for access</h3>
                    <p class="mt-3 mb-2">
                        In order to access this plane, you will need to have the following quest item:
                    </p>
                    <ul class="my-4">
                        <li>
                            <x-core.buttons.link-buttons.primary-button
                                href="{{ route('info.page.item', ['item' => $itemNeeded->id]) }}">
                                {{ $itemNeeded->name }}
                            </x-core.buttons.link-buttons.primary-button>
                        </li>
                    </ul>
                @endif

                @if (!is_null($walkOnWater))
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2'></div>
                    <h3 class="my-4">Item required to walk on
                        @if ($map->mapType()->isHell())
                            Magma
                        @elseif ($map->mapType()->isDungeons())
                            Death Water
                        @elseif ($map->mapType()->isTheIcePlane())
                            Ice
                        @else
                            Water
                        @endif
                    </h3>
                    <p class="mt-3 mb-2">
                        Some planes require you to have a special item to walk on that planes water. This is one such
                        plane.
                        Below you can click the button to learn more about where to get the item you need.
                    </p>
                    <ul class="my-4">
                        <li>
                            <x-core.buttons.link-buttons.primary-button
                                href="{{ route('info.page.item', ['item' => $walkOnWater->id]) }}">
                                {{ $walkOnWater->name }}
                            </x-core.buttons.link-buttons.primary-button>
                        </li>
                    </ul>
                @endif
            </div>
        </div>
    </x-core.cards.card-with-title>

</x-core.layout.info-container>
