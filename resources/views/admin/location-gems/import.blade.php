@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title title="Import Location Gems" buttons="true" backUrl="{{ route('admin.location-gems.list') }}">
            <form method="POST" action="{{ route('admin.location-gems.import') }}" enctype="multipart/form-data">
                @csrf
                <x-core.forms.file-upload label="Location Gems File" name="location_gems_import" accept=".xlsx" required />
                <x-core.buttons.primary-button type="submit">Import</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
