@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Npcs"
            buttons="true"
            backUrl="{{route('npcs.index')}}"
        >
            <form method="POST" action="{{ route('npcs.export-data') }}" class="mb-4 text-center">
                @csrf
                <x-core.buttons.primary-button type="submit">Export NPCs</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection

