@extends('layouts.admin')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title title="Export Location Templates" buttons="true" backUrl="{{ route('admin.location-templates.list') }}">
            <form method="POST" action="{{ route('admin.location-templates.export') }}" class="text-center">
                @csrf
                <x-core.buttons.primary-button type="submit">Export</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
