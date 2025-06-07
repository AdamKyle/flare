@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Races"
            buttons="true"
            backUrl="{{route('races.list')}}"
        >
            <form
                method="POST"
                action="{{ route('races.export') }}"
                class="mb-4 text-center"
            >
                @csrf
                <x-core.buttons.primary-button type="submit">
                    Export Races
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
