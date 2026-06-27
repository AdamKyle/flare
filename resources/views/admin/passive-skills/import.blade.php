@extends('layouts.app')

@section('content')

    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Passives"
            buttons="true"
            backUrl="{{route('passive.skills.list')}}"
        >
            <form class="mt-4" action="{{route('passive.skills.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <x-core.forms.file-upload label="Passives File" name="passives_import" />
                <x-core.buttons.primary-button type="submit">Import Passives</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
