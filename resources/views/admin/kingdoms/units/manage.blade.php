@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{!is_null($unit) ? 'Edit: ' . nl2br($unit->name) : 'Create New Unit'}}"
            buttons="true"
            backUrl="{{!is_null($unit) ? route('units.unit', ['gameUnit' => $unit->id]) : route('units.list')}}"
        >

            <x-core.form-wizard.container action="{{route('units.store')}}" modelId="{{!is_null($unit) ? $unit->id : 0}}" lastTab="tab-style-2-2">
                <x-core.form-wizard.tabs>
                    <x-core.form-wizard.tab target="tab-style-2-1" primaryTitle="Basic Info" secondaryTitle="Basic unit info." isActive="true"/>
                    <x-core.form-wizard.tab target="tab-style-2-2" primaryTitle="Resource Costs" secondaryTitle="Resources Costs per unit"/>
                </x-core.form-wizard.tabs>

                <x-core.form-wizard.contents>
                    <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
                        <div class="grid md:grid-cols-1 gap-2">
                            <div>
                                <h3 class="mb-3">Basic Unit Info</h3>
                                <x-core.forms.input :model="$unit" label="Name:" modelKey="name" name="name" type="text"/>
                                <x-core.forms.text-area :model="$unit" label="Description:" modelKey="description" name="description" />

                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Attack and Defence (per unit)</h3>
                                <x-core.forms.input :model="$unit" label="Attack:" modelKey="attack" name="attack" type="number" />
                                <x-core.forms.input :model="$unit" label="Defence:" modelKey="defence" name="defence" type="number" />

                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Healing (Optional)</h3>
                                <x-core.forms.check-box :model="$unit" label="Can this unit heal?" modelKey="can_heal" name="can_heal" />
                                <x-core.forms.input :model="$unit" label="Heal (%):" modelKey="heal_percentage" name="heal_percentage" type="number" />

                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Settler (Optional)</h3>
                                <x-core.forms.check-box :model="$unit" label="Is Settler?" modelKey="is_settler" name="is_settler" />
                                <x-core.forms.input :model="$unit" label="Reduces Morale By (%):" modelKey="reduces_morale_by" name="reduces_morale_by" type="number" />

                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">(Other) Type Of Unit</h3>
                                <x-core.forms.check-box :model="$unit" label="Attacker?" modelKey="attacker" name="attacker" />
                                <x-core.forms.check-box :model="$unit" label="Defender?" modelKey="defender" name="defender" />
                                <x-core.forms.check-box :model="$unit" label="Is Siege?" modelKey="siege_weapon" name="siege_weapon" />
                                <x-core.forms.check-box :model="$unit" label="Is Airship?" modelKey="is_airship" name="is_airship" />

                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Misc. (Optional)</h3>
                                <x-core.forms.check-box :model="$unit" label="Cannot be healed?" modelKey="can_not_be_healed" name="can_not_be_healed" />

                                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-6'></div>
                                <h3 class="mb-3">Time to Recruit (per 1 unit in seconds)</h3>
                                <x-core.forms.input :model="$unit" label="Time to Recruit:" modelKey="time_to_recruit" name="time_to_recruit" type="number" />
                            </div>
                        </div>
                    </x-core.form-wizard.content>

                    <x-core.form-wizard.content target="tab-style-2-2" isOpen="false">
                        <div class="grid md:grid-cols-1 gap-2">
                            <div>
                                <h3 class="mb-3">Unit Cost (per 1 unit)</h3>
                                <x-core.forms.input :model="$unit" label="Wood Cost:" modelKey="wood_cost" name="wood_cost" type="number"/>
                                <x-core.forms.input :model="$unit" label="Stone Cost:" modelKey="stone_cost" name="stone_cost" type="number"/>
                                <x-core.forms.input :model="$unit" label="Clay Cost:" modelKey="clay_cost" name="clay_cost" type="number"/>
                                <x-core.forms.input :model="$unit" label="Iron Cost:" modelKey="iron_cost" name="iron_cost" type="number"/>
                                <x-core.forms.input :model="$unit" label="Steel Cost:" modelKey="steel_cost" name="steel_cost" type="number"/>
                                <x-core.forms.input :model="$unit" label="Population Cost:" modelKey="required_population" name="required_population" type="number"/>

                            </div>
                        </div>
                    </x-core.form-wizard.content>
                </x-core.form-wizard.contents>
            </x-core.form-wizard.container>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>

@endsection
