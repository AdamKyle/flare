<x-core.layout.info-container>
    <x-core.cards.card-with-title
        title="{{$raid->name}}"
        buttons="true"
        backUrl="{{url()->previous()}}"
        editUrl="{{route('admin.raids.edit', ['raid' => $raid])}}"
    >
        <h3 class="my-4">Story</h3>
        <div class="mb-4"> {!! nl2br($raid->story) !!} </div>

        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <strong class="my-4">Raid Boss</strong>
        <p class="my-4">
            <a href="/information/monster/{{$raid->raid_boss_id}}" target="_blank"><i class="fas fa-external-link-alt"></i> {{$raid->raidBoss->name}}</a> who you will find
            at <a href="/information/location/{{$raid->raid_boss_location_id}}" target="_blank"><i class="fas fa-external-link-alt"></i> {{$raid->raidBossLocation->name}}</a>
        </p>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <strong class="my-4">Raid Monsters</strong>
        <div class="my-4 grid md:grid-cols-2 gap-2">
            @foreach($raidMonsters as $raidMonsterChunk)
                <div>
                    <ul class="list-disc ml-8">
                        @foreach($raidMonsterChunk as $monster)
                            <li><a href="/information/monster/{{$monster['id']}}" target="_blank"><i class="fas fa-external-link-alt"></i>{{$monster['name']}}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <strong class="my-4">Corrupted Locations</strong>
        <p class="my-4">
            These are locations where the monster list will change from regular critters to that of the raid monsters.
        </p>
        @livewire('admin.locations.locations-table', [
            'locationIds' => $raid->corrupted_location_ids
        ])
    </x-core.cards.card-with-title>
</x-core.layout.info-container>
