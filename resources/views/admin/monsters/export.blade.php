@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Monsters"
            buttons="true"
            backUrl="{{route('monsters.list')}}"
        >
            <div class="grid grid-cols-1 gap-3 items-center">
                <form method="POST" action="{{ route('monsters.export-data') }}" class="text-center">
                    @csrf
                    <input type="hidden" value="monster" name="monster_type" />
                    <x-core.buttons.primary-button type="submit">
                        Export Monsters
                    </x-core.buttons.primary-button>
                </form>

                <form method="POST" action="{{ route('monsters.export-data') }}" class="text-center">
                    @csrf
                    <input type="hidden" value="celestial" name="monster_type" />
                    <x-core.buttons.primary-button type="submit">
                        Export Celestials
                    </x-core.buttons.primary-button>
                </form>

                <form method="POST" action="{{ route('monsters.export-data') }}" class="text-center">
                    @csrf
                    <input type="hidden" value="raid_monster" name="monster_type" />
                    <x-core.buttons.primary-button type="submit">
                        Export Raid Monsters
                    </x-core.buttons.primary-button>
                </form>

                <form method="POST" action="{{ route('monsters.export-data') }}" class="text-center">
                    @csrf
                    <input type="hidden" value="raid_boss" name="monster_type" />
                    <x-core.buttons.primary-button type="submit">
                        Export Raid Bosses
                    </x-core.buttons.primary-button>
                </form>
            </div>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
