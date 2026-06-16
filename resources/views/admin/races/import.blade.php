@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Races"
            buttons="true"
            backUrl="{{route('races.list')}}"
        >
            <form class="mt-4" action="{{route('races.import')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <x-core.forms.file-upload label="Races File" name="races_import" />
                <x-core.buttons.primary-button type="submit">Import Races</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
