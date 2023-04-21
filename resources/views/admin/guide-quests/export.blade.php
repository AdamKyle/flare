@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Guide Quests"
            buttons="true"
            backUrl="{{route('admin.guide-quests')}}"
        >
            <form method="POST" action="{{ route('admin.guide-quests.export-data') }}" class="mb-4 text-center">
                @csrf
                <x-core.buttons.primary-button type="submit">Export Guide Quests</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
