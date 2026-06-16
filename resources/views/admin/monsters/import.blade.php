@extends('layouts.app')

@section('content')

    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Monsters"
            buttons="true"
            backUrl="{{route('monsters.list')}}"
        >
            <div class="mt-4 mb-4">
                <x-core.alerts.info-alert title="ATTN!">
                    If a quest item or game map does not exist, the monster will be skipped.
                </x-core.alerts.info-alert>
            </div>
            <form class="mt-4" action="{{route('monsters.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <x-core.forms.file-upload label="Monster File" name="monsters_import" />
                <x-core.buttons.primary-button type="submit">Import Monsters</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
