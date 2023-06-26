@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Items"
            buttons="true"
            backUrl="{{route('items.list')}}"
        >
            <form method="POST" action="{{ route('items.export-data') }}" class="mb-4 text-center">
                @csrf
                <input value="weapons" name="type_to_export" type="hidden" />
                <x-core.buttons.primary-button type="submit">Export Weapons Only</x-core.buttons.primary-button>
            </form>
            <form method="POST" action="{{ route('items.export-data') }}" class="mb-4 text-center">
                @csrf
                <input value="armour" name="type_to_export" type="hidden" />
                <x-core.buttons.primary-button type="submit">Export Armour Only</x-core.buttons.primary-button>
            </form>
            <form method="POST" action="{{ route('items.export-data') }}" class="mb-4 text-center">
                @csrf
                <input value="rings" name="type_to_export" type="hidden" />
                <x-core.buttons.primary-button type="submit">Export Rings Only</x-core.buttons.primary-button>
            </form>
            <form method="POST" action="{{ route('items.export-data') }}" class="mb-4 text-center">
                @csrf
                <input value="spells" name="type_to_export" type="hidden" />
                <x-core.buttons.primary-button type="submit">Export Spells Only</x-core.buttons.primary-button>
            </form>
            <form method="POST" action="{{ route('items.export-data') }}" class="mb-4 text-center">
                @csrf
                <input value="quest" name="type_to_export" type="hidden" />
                <x-core.buttons.primary-button type="submit">Export Quest Items Only</x-core.buttons.primary-button>
            </form>
            <form method="POST" action="{{ route('items.export-data') }}" class="mb-4 text-center">
                @csrf
                <input value="alchemy" name="type_to_export" type="hidden" />
                <x-core.buttons.primary-button type="submit">Export Alchemy Items Only</x-core.buttons.primary-button>
            </form>
            <form method="POST" action="{{ route('items.export-data') }}" class="mb-4 text-center">
                @csrf
                <input value="trinket" name="type_to_export" type="hidden" />
                <x-core.buttons.primary-button type="submit">Export Trinkets Only</x-core.buttons.primary-button>
            </form>
            <form method="POST" action="{{ route('items.export-data') }}" class="mb-4 text-center">
                @csrf
                <input value="artifact" name="type_to_export" type="hidden" />
                <x-core.buttons.primary-button type="submit">Export Artifacts Only</x-core.buttons.primary-button>
            </form>
            <form method="POST" action="{{ route('items.export-data') }}" class="mb-4 text-center">
                @csrf
                <input value="specialty-shops" name="type_to_export" type="hidden" />
                <x-core.buttons.primary-button type="submit">Export Specialty Shops Only</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
