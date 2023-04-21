@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Quests"
            buttons="true"
            backUrl="{{route('quests.index')}}"
        >
            <div class="mt-4 mb-4">
                <x-core.alerts.info-alert title="Attn!">
                    <p class="mt-2 mb-2">
                        Make sure you have imported items, skills, locations and so on that match this import data, or we will ignore
                        specific aspects of the data and set it to "empty" for the respected value. IE, if you have a faction requirement and that map does not exist
                        then the quest(s) with that requirement will no longer have that as a requirement, and you will have to go back and manually set it or re-import.
                    </p>
                </x-core.alerts.info-alert>
            </div>
            <form class="mt-4" action="{{route('quests.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="form-group mb-4">
                    <label for="quests_import">Quests File</label>
                    <input type="file" class="form-control" id="quests_import" aria-describedby="quests_import" name="quests_import">
                </div>
                <x-core.buttons.primary-button type="submit">Import Quests</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
