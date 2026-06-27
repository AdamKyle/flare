@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title title="Import Map Gems" buttons="true" backUrl="{{ route('admin.map-gems.list') }}">
            <form method="POST" action="{{ route('admin.map-gems.import') }}" enctype="multipart/form-data">
                @csrf
                <x-core.forms.file-upload label="Map Gems File" name="map_gems_import" accept=".xlsx" required />
                <x-core.buttons.primary-button type="submit">Import</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
