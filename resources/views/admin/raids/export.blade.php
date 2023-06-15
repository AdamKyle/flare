@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Raids"
            buttons="true"
            backUrl="{{route('admin.raids.list')}}"
        >
            <form method="POST" action="{{ route('admin.raids.export') }}" class="mb-4 text-center">
                @csrf
                <x-core.buttons.primary-button type="submit">Export Raids</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
