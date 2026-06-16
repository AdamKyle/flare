@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import NPCs"
            buttons="true"
            backUrl="{{route('npcs.index')}}"
        >
            <form class="mt-4" action="{{route('npcs.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <x-core.forms.file-upload label="Npc's File" name="npcs_import" />
                <x-core.buttons.primary-button type="submit">Import NPCs</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
