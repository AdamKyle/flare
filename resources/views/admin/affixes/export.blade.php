@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Affixes"
            buttons="true"
            backUrl="{{route('affixes.list')}}"
        >
            <form method="POST" action="{{ route('affixes.export-data') }}" class="text-center">
                @csrf
                <x-core.buttons.primary-button type="submit">
                    Export
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
