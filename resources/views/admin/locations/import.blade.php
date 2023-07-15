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
                <div class="mb-5">
                    <label class="label block mb-2" for="locations_import">Locations File</label>
                    <input id="locations_import" type="file" class="form-control" name="locations_import" />
                </div>
                <x-core.buttons.primary-button type="submit">Import Locations</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
