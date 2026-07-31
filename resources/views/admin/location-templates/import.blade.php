@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title title="Import Location Templates" buttons="true" backUrl="{{ route('admin.location-templates.list') }}">
            <form method="POST" action="{{ route('admin.location-templates.import') }}" enctype="multipart/form-data">
                @csrf
                <x-core.forms.file-upload label="Location Templates File" name="location_templates_import" accept=".xlsx" required />
                <x-core.buttons.primary-button type="submit">Import</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
