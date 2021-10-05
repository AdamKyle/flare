@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row page-titles">
        <div class="col-md-6 align-self-left">
            <h4 class="mt-3">Monsters</h4>
        </div>
        <div class="col-md-6 align-self-right">
            <a href="{{route('home')}}" class="btn btn-success float-right ml-2">Home</a>
            <a href="{{route('monsters.create')}}" class="btn btn-primary float-right ml-2">Create</a>
        </div>
    </div>
    <hr />
    <x-tabs.pill-tabs-container>
        @foreach($gameMapNames as $index => $gameMapName)
            <x-tabs.tab
              tab="{{$gameMapName . '-' . $index}}"
              selected="{{$index === 0 ? 'true' : 'false'}}"
              active="{{$index === 0 ? 'true' : 'false'}}"
              title="{{$gameMapName}}"
            />
        @endforeach
    </x-tabs.pill-tabs-container>
    <x-tabs.tab-content>
        @foreach($gameMapNames as $index => $gameMapName)
            <x-tabs.tab-content-section
              tab="{{$gameMapName . '-' . $index}}"
              active="{{$index === 0 ? 'true' : 'false'}}"
            >
                <x-cards.card>
                    <x-tabs.pill-tabs-container>
                        <x-tabs.tab
                          tab="{{$gameMapName . '-' . $index . '-monsters'}}"
                          selected="true"
                          active="true"
                          title="Monsters"
                        />
                        <x-tabs.tab
                          tab="{{$gameMapName . '-' . $index . '-celestials'}}"
                          selected="false"
                          active="false"
                          title="Celestials"
                        />
                    </x-tabs.pill-tabs-container>
                    <x-tabs.tab-content>
                        <x-tabs.tab-content-section
                          tab="{{$gameMapName . '-' . $index . '-monsters'}}"
                          active="true"
                        >
                            @livewire('admin.monsters.data-table', [
                                'onlyMapName' => $gameMapName,
                            ])
                        </x-tabs.tab-content-section>
                        <x-tabs.tab-content-section
                          tab="{{$gameMapName . '-' . $index . '-celestials'}}"
                          active="true"
                        >
                            @livewire('admin.monsters.data-table', [
                                'onlyMapName' => $gameMapName,
                                'withCelestials' => true,
                            ])
                        </x-tabs.tab-content-section>
                    </x-tabs.tab-content>
                </x-cards.card>
            </x-tabs.tab-content-section>
        @endforeach
    </x-tabs.tab-content>


    <div class="mb-2">
        <h5>Awaiting approval</h5>
        @livewire('admin.monsters.data-table', [
            'published' => false
        ])
    </div>
</div>
@endsection
