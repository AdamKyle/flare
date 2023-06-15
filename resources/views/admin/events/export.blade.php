@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Scheduled Events"
            buttons="true"
            backUrl="{{route('admin.events')}}"
        >
            <form method="POST" action="{{ route('admin.events.export') }}">
                @csrf
                <div class="text-center">
                    <x-core.buttons.primary-button type="submit">
                        Export
                    </x-core.buttons.primary-button>
                </div>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
