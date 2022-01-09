@extends('layouts.app')

@section('content')

    <div class="tw-mt-10 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
        <x-core.page-title
          title="Monsters & Celestials"
          route="{{route('home')}}"
          color="success" link="Home"
        >
            <x-core.buttons.link-buttons.primary-button
              href="{{route('monsters.create')}}"
              css="tw-ml-2"
            >
                Create Monster
            </x-core.buttons.link-buttons.primary-button>
            <x-core.buttons.link-buttons.primary-button
              href="{{route('monsters.export')}}"
              css="tw-ml-2"
            >
                <i class="fas fa-file-export"></i> Export
            </x-core.buttons.link-buttons.primary-button>
            <x-core.buttons.link-buttons.primary-button
              href="{{route('monsters.import')}}"
              css="tw-ml-2"
            >
                <i class="fas fa-file-upload"></i> Import
            </x-core.buttons.link-buttons.primary-button>
        </x-core.page-title>

        <x-tabs.pill-tabs-container>
            @foreach($gameMapNames as $index => $gameMapName)
                @php $name = str_replace(' ', '-', $gameMapName)@endphp
                <x-tabs.tab
                  tab="{{$name . '-' . $index}}"
                  selected="{{$index === 0 ? 'true' : 'false'}}"
                  active="{{$index === 0 ? 'true' : 'false'}}"
                  title="{{$gameMapName}}"
                />
            @endforeach
        </x-tabs.pill-tabs-container>
        <x-tabs.tab-content>
            @foreach($gameMapNames as $index => $gameMapName)
                @php $name = str_replace(' ', '-', $gameMapName)@endphp
                <x-tabs.tab-content-section
                  tab="{{$name . '-' . $index}}"
                  active="{{$index === 0 ? 'true' : 'false'}}"
                >
                    <x-core.cards.card>
                        <x-tabs.pill-tabs-container>
                            <x-tabs.tab
                              tab="{{$name . '-' . $index . '-monsters'}}"
                              selected="true"
                              active="true"
                              title="Monsters"
                            />
                            <x-tabs.tab
                              tab="{{$name . '-' . $index . '-celestials'}}"
                              selected="false"
                              active="false"
                              title="Celestials"
                            />
                            <x-tabs.tab
                              tab="{{$name . '-' . $index . '-approval'}}"
                              selected="false"
                              active="false"
                              title="Needs Approval"
                            />
                        </x-tabs.pill-tabs-container>
                        <x-tabs.tab-content>
                            <x-tabs.tab-content-section
                              tab="{{$name . '-' . $index . '-monsters'}}"
                              active="true"
                            >
                                @livewire('admin.monsters.data-table', [
                                    'onlyMapName' => $gameMapName,
                                ])
                            </x-tabs.tab-content-section>
                            <x-tabs.tab-content-section
                              tab="{{$name . '-' . $index . '-celestials'}}"
                              active="false"
                            >
                                @livewire('admin.monsters.data-table', [
                                    'onlyMapName' => $gameMapName,
                                    'only' => 'celestials',
                                ])
                            </x-tabs.tab-content-section>

                            <x-tabs.tab-content-section
                              tab="{{$name . '-' . $index . '-approval'}}"
                              active="false"
                            >
                                @livewire('admin.monsters.data-table', [
                                    'onlyMapName'    => $gameMapName,
                                    'published'      => false,
                                    'withCelestials' => true,
                                ])
                            </x-tabs.tab-content-section>
                        </x-tabs.tab-content>
                    </x-core.cards.card>
                </x-tabs.tab-content-section>
            @endforeach
        </x-tabs.tab-content>
    </div>
@endsection
