<p class="mb-3 mt-3">{{ $unit->description }}</p>

@if (! is_null($building))
    @guest
        <p class="mt-4 mb-4">
            <strong>
                Can be recruited from:
                <a
                    href="{{
                        route('game.buildings.building', [
                            'building' => $building->id,
                        ])
                    }}"
                >
                    {{ $building->name }}
                </a>
                At Level: {{ $requiredLevel }}
            </strong>
        </p>
    @else
        @if (auth()->user()->hasRole('Admin'))
            <p class="mt-4 mb-4">
                <strong>
                    Can be recruited from:
                    <a
                        href="{{
                            route('buildings.building', [
                                'building' => $building->id,
                            ])
                        }}"
                    >
                        {{ $building->name }}
                    </a>
                    At Level: {{ $requiredLevel }}
                </strong>
            </p>
        @else
            <p class="mt-4 mb-4">
                <strong>
                    Can be recruited from:
                    <a
                        href="{{
                            route('game.buildings.building', [
                                'building' => $building->id,
                            ])
                        }}"
                    >
                        {{ $building->name }}
                    </a>
                    At Level: {{ $requiredLevel }}
                </strong>
            </p>
        @endif
    @endguest
@endif

<div class="grid md:grid-cols-2 gap-4">
    <div>
        <h3>Base Details</h3>
        <div
            class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"
        ></div>
        <dl>
            <dd>
                <strong>Is Attacker?</strong>
                :
            </dd>
            <dd>{{ $unit->attacker ? 'Yes' : 'No' }}</dd>
            <dd>
                <strong>Is Defender?</strong>
                :
            </dd>
            <dd>{{ $unit->defender ? 'Yes' : 'No' }}</dd>
            <dd>
                <strong>Attack</strong>
                :
            </dd>
            <dd>{{ $unit->attack }}</dd>
            <dd>
                <strong>Defence</strong>
                :
            </dd>
            <dd>{{ $unit->defence }}</dd>
            <dd>
                <strong>Is Siege Weapon?</strong>
                :
            </dd>
            <dd>{{ $unit->siege_weapon ? 'Yes' : 'No' }}</dd>
            <dd>
                <strong>Is Airship?</strong>
                :
            </dd>
            <dd>{{ $unit->is_airship ? 'Yes' : 'No' }}</dd>
            <dd>
                <strong>Can Heal?</strong>
                :
            </dd>
            <dd>{{ $unit->can_heal ? 'Yes' : 'No' }}</dd>
        </dl>
        <div
            class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"
        ></div>
        <h3>Time Details</h3>
        <div
            class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"
        ></div>
        <dl>
            <dd>
                <strong>Time To Recruit</strong>
                :
            </dd>
            <dd>{{ $unit->time_to_recruit }} Seconds</dd>
        </dl>
        <div
            class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"
        ></div>
        <h3>Is Special Unit?</h3>
        <div
            class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"
        ></div>
        <p class="my-2">
            Special units cannot be purchased with gold and thus require you to
            use the kingdoms resources.
        </p>
        <dl>
            <dd>
                <strong>Is Special</strong>
                :
            </dd>
            <dd>{{ $unit->is_special ? 'Yes' : 'No' }}</dd>
        </dl>
    </div>
    <div
        class="border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6"
    ></div>
    <div>
        <h3>Cost Details</h3>
        <div
            class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6"
        ></div>
        <dl>
            <dd>
                <strong>Cost in wood</strong>
                :
            </dd>
            <dd>{{ $unit->wood_cost }}</dd>
            <dd>
                <strong>Cost in clay</strong>
                :
            </dd>
            <dd>{{ $unit->clay_cost }}</dd>
            <dd>
                <strong>Cost in stone</strong>
                :
            </dd>
            <dd>{{ $unit->stone_cost }}</dd>
            <dd>
                <strong>Cost in iron</strong>
                :
            </dd>
            <dd>{{ $unit->iron_cost }}</dd>
            <dd>
                <strong>Cost in steel</strong>
                :
            </dd>
            <dd>{{ $unit->steel_cost }}</dd>
            <dd>
                <strong>Required population</strong>
                :
            </dd>
            <dd>{{ $unit->required_population }}</dd>
        </dl>
    </div>
</div>
