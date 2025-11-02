@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="Export Monsters"
      buttons="true"
      backUrl="{{route('monsters.list')}}"
    >
      <div class="grid grid-cols-1 items-center gap-3">
        <form
          method="POST"
          action="{{ route('monsters.export-data') }}"
          class="text-center"
        >
          @csrf
          <input type="hidden" value="monsters" name="monster_type" />
          <x-core.buttons.primary-button type="submit">
            Export Monsters
          </x-core.buttons.primary-button>
        </form>

        <form
          method="POST"
          action="{{ route('monsters.export-data') }}"
          class="text-center"
        >
          @csrf
          <input type="hidden" value="celestials" name="monster_type" />
          <x-core.buttons.primary-button type="submit">
            Export Celestials
          </x-core.buttons.primary-button>
        </form>

        <form
          method="POST"
          action="{{ route('monsters.export-data') }}"
          class="text-center"
        >
          @csrf
          <input type="hidden" value="raid-monsters" name="monster_type" />
          <x-core.buttons.primary-button type="submit">
            Export Raid Monsters
          </x-core.buttons.primary-button>
        </form>

        <form
          method="POST"
          action="{{ route('monsters.export-data') }}"
          class="text-center"
        >
          @csrf
          <input type="hidden" value="raid-bosses" name="monster_type" />
          <x-core.buttons.primary-button type="submit">
            Export Raid Bosses
          </x-core.buttons.primary-button>
        </form>

        <form
          method="POST"
          action="{{ route('monsters.export-data') }}"
          class="text-center"
        >
          @csrf
          <input type="hidden" value="weekly-monsters" name="monster_type" />
          <x-core.buttons.primary-button type="submit">
            Weekly Fight Monsters
          </x-core.buttons.primary-button>
        </form>
      </div>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
