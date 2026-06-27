@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Locations"
            buttons="true"
            backUrl="{{route('locations.list')}}"
        >
            <form class="mt-4" action="{{route('locations.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <x-core.forms.file-upload label="Locations File" name="locations_import" />
                <x-core.buttons.primary-button type="submit">Import Locations</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
