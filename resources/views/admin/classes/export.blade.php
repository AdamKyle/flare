@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Classes"
            buttons="true"
            backUrl="{{route('classes.list')}}"
        >
            <form method="POST" action="{{ route('classes.export-data') }}" class="mb-4 text-center">
                @csrf
                <x-core.buttons.primary-button type="submit">Export Classes</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
