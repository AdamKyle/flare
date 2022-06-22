<x-core.layout.info-container>
    @php
        $backUrl = route('buildings.list');

        if (!auth()->user()->hasRole('Admin')) {
            $backUrl = '/information/kingdom-buildings';
        }
    @endphp

    <x-core.cards.card-with-title
        title="{{$building->name}}"
        buttons="true"
        backUrl="{{$backUrl}}"
        editUrl="{{route('buildings.edit', ['building' => $building->id])}}"
    >
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <h3>Base Details</h3>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                <p class="mb-4">{{$building->description}}</p>

                <dl>
                    <dd><strong>Max Level</strong>:</dd>
                    <dd>{{$building->max_level}}</dd>
                    <dd><strong>Base Durability</strong>:</dd>
                    <dd>{{$building->base_durability}}</dd>
                    <dd><strong>Base Defence</strong>:</dd>
                    <dd>{{$building->base_defence}}</dd>
                    <dd><strong>Required Population</strong>:</dd>
                    <dd>{{$building->required_population}}</dd>
                    <dd><strong>Is Walls?</strong>:</dd>
                    <dd>{{$building->is_walls ? 'Yes' : 'No'}}</dd>
                    <dd><strong>Is Farm?</strong>:</dd>
                    <dd>{{$building->is_farm ? 'Yes' : 'No'}}</dd>
                    <dd><strong>Is Church?</strong>:</dd>
                    <dd>{{$building->is_church ? 'Yes' : 'No'}}</dd>
                    <dd><strong>Does this building generate resources?</strong>:</dd>
                    <dd>{{$building->is_resource_building ? 'Yes' : 'No'}}</dd>
                </dl>
            </div>
            <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
            <div>
                <h3>Requirements</h3>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                <x-core.alerts.info-alert title="Time Calculation Past Level 1">
                    <p>Time is increased by <code>level + 1 * time required * time increased by</code>.</p>
                </x-core.alerts.info-alert>
                <dl>
                    <dd><strong>Time Required</strong>:</dd>
                    <dd>{{$building->time_to_build}}</dd>
                    <dd><strong>Time Increased By</strong>:</dd>
                    <dd>{{$building->time_increase_amount * 100}}%</dd>
                </dl>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                <x-core.alerts.info-alert title="Cost Calculation">
                    <p>
                        Below is the cost for one level, that is from level 1 to level 2. For every level past that, its the
                        <code>building level + 1 * resource cost</code>.
                    </p>
                </x-core.alerts.info-alert>
                <dl>
                    <dd><strong>Cost in wood</strong>:</dd>
                    <dd>{{$building->wood_cost}}</dd>
                    <dd><strong>Cost in clay</strong>:</dd>
                    <dd>{{$building->clay_cost}}</dd>
                    <dd><strong>Cost in stone</strong>:</dd>
                    <dd>{{$building->stone_cost}}</dd>
                    <dd><strong>Cost in iron</strong>:</dd>
                    <dd>{{$building->iron_cost}}</dd>
                </dl>
            </div>
        </div>

        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
        <div class="grid md:grid-cols-2 gap-4">
            <div>
                <x-core.alerts.info-alert title="Resource Calculation">
                    <p>
                        Below is what you would get at level 1, should this building provide resources. For every level past that, for example level 2, it would
                        be: <code>level + 1 * Increase X</code>, where X is the Resource type this building increases.
                    </p>
                </x-core.alerts.info-alert>
                <dl>
                    <dd><strong>Increases Population</strong>:</dd>
                    <dd>{{!is_null($building->increase_population_amount) ? $building->increase_population_amount : 0}}</dd>
                    <dd><strong>Increases Wood</strong>:</dd>
                    <dd>{{!is_null($building->future_increase_wood_amount) ? $building->future_increase_wood_amount : 0}}</dd>
                    <dd><strong>Increases Clay</strong>:</dd>
                    <dd>{{!is_null($building->future_increase_clay_amount) ? $building->future_increase_clay_amount : 0}}</dd>
                    <dd><strong>Increases Stone</strong>:</dd>
                    <dd>{{!is_null($building->future_increase_stone_amount) ? $building->future_increase_stone_amount : 0}}</dd>
                    <dd><strong>Increases Iron</strong>:</dd>
                    <dd>{{!is_null($building->future_increase_iron_amount) ? $building->future_increase_iron_amount : 0}}</dd>
                    <dd><strong>Increases Durability</strong>:</dd>
                    <dd>{{!is_null($building->future_increase_durability_amount) ? $building->future_increase_durability_amount : 0}}</dd>
                    <dd><strong>Increases Defense</strong>:</dd>
                    <dd>{{!is_null($building->future_increase_defence_amount) ? $building->future_increase_defence_amount : 0}}</dd>
                </dl>
            </div>
            <div class='border-b-2 block md:hidden border-b-gray-300 dark:border-b-gray-600 my-6'></div>
            <div>
                <x-core.alerts.info-alert title="Morale Calculation">
                    <p>These are applied per hour. Morale decrease only applies if
                        this building falls to 0 durability and the building decreases the morale.</p>
                </x-core.alerts.info-alert>
                <dl>
                    <dd><strong>Increases Morale</strong>:</dd>
                    <dd>{{$building->increase_morale_amount * 100}}%</dd>
                    <dd><strong>Decrease Morale</strong>:</dd>
                    <dd>{{$building->decrease_morale_amount * 100}}%</dd>
                </dl>
            </div>
        </div>
    </x-core.cards.card-with-title>
    <div class="mt-5">
      <h4 class="mb-4">Units this building can recruit</h4>
      @livewire('admin.kingdoms.units.units-table', [ 'building' => $building])
    </div>
</x-core.layout.info-container>
