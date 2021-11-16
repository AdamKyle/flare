
<x-core.alerts.info-alert title="Please note">
    <p>These values represent the recruitment of one unit. These values are then
        multiplied by the amount of units you want to recruit.</p>
    <p>For Movement, the total time shown is for all units of this type, regardless of amount.</p>
</x-core.alerts.info-alert>
<p class="mb-3 mt-3">{{$unit->description}}</p>
@if (!is_null($building))
    @guest
        <p><strong>Can be recruited from: <a href="{{route('game.buildings.building', [
                'building' => $building->id
            ])}}">{{$building->name}}</a> At Level: {{$requiredLevel}}</strong></p>
    @else
        @if (auth()->user()->hasRole('Admin')))
        <p><strong>Can be recruited from: <a href="{{route('buildings.building', [
                    'building' => $building->id
                ])}}">{{$building->name}}</a> At Level: {{$requiredLevel}}</strong></p>
        @else
            <p><strong>Can be recruited from: <a href="{{route('game.buildings.building', [
                    'building' => $building->id
                ])}}">{{$building->name}}</a> At Level: {{$requiredLevel}}</strong></p>
        @endif
    @endguest

@endif
<x-core.tabs.container ulCss="tw-justify-center" useHr="true" tabsId="unit-details" contentId="unit-details-section">
    <x-slot name="tabs">
        <x-core.tabs.tab active="true"  id="unit-costs" href="unit-costs">Cost</x-core.tabs.tab>
        <x-core.tabs.tab active="false" id="unit-time-info" href="unit-time-info">Recruit/Movement Time</x-core.tabs.tab>
        <x-core.tabs.tab active="false" id="unit-attack-info" href="unit-attack-info">Unit Stats/Attack</x-core.tabs.tab>
    </x-slot>
    <x-slot name="content">
        <x-core.tabs.tab-content active="true" id="unit-costs">
            <dl>
                <dd><strong>Cost in wood</strong>:</dd>
                <dd>{{$unit->wood_cost}}</dd>
                <dd><strong>Cost in clay</strong>:</dd>
                <dd>{{$unit->clay_cost}}</dd>
                <dd><strong>Cost in stone</strong>:</dd>
                <dd>{{$unit->stone_cost}}</dd>
                <dd><strong>Cost in iron</strong>:</dd>
                <dd>{{$unit->iron_cost}}</dd>
                <dd><strong>Required population</strong>:</dd>
                <dd>{{$unit->required_population}}</dd>
            </dl>
        </x-core.tabs.tab-content>
        <x-core.tabs.tab-content active="false" id="unit-time-info">
            <dl>
                <dd><strong>Travel Time</strong>:</dd>
                <dd>{{$unit->travel_time}} Minutes</dd>
                <dd><strong>Time To Recruit</strong>:</dd>
                <dd>{{$unit->time_to_recruit}} Minutes</dd>
            </dl>
        </x-core.tabs.tab-content>
        <x-core.tabs.tab-content active="false" id="unit-attack-info">
            <dl>
                <dd><strong>Is Atacker?</strong>:</dd>
                <dd>{{$unit->atacker ? 'Yes' : 'No'}}</dd>
                <dd><strong>Is Defender?</strong>:</dd>
                <dd>{{$unit->defender ? 'Yes' : 'No'}}</dd>
                <dd><strong>Attack</strong>:</dd>
                <dd>{{$unit->attack}}</dd>
                <dd><strong>Defence</strong>:</dd>
                <dd>{{$unit->defence}}</dd>
                <dd><strong>Is Siege Weapon?</strong>:</dd>
                <dd>{{$unit->seige_weapon ? 'Yes' : 'No'}}</dd>
                <dd><strong>Can Heal?</strong>:</dd>
                <dd>{{$unit->can_heal ? 'Yes' : 'No'}}</dd>
            </dl>
        </x-core.tabs.tab-content>
    </x-slot>
</x-core.tabs.container>

@push('scripts')
    <script src="{{mix('js/page-components/tabs.js')}}" type="text/javascript"></script>

    <script>
        pageComponentTabs('#unit-details', '#unit-details-section')
    </script>
@endpush
