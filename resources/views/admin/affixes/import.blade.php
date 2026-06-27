@extends('layouts.app')

@section('content')

    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Affixes"
            buttons="true"
            backUrl="{{route('affixes.list')}}"
        >
            <div class="mt-4 mb-4">
                <x-core.alerts.info-alert title="ATTN!">
                    If an affix affects a skill that does not exist, the affix will be skipped.
                </x-core.alerts.info-alert>
            </div>
            <form class="mt-4" action="{{route('affixes.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <x-core.forms.file-upload label="Affixes File" name="affixes_import" />
                <x-core.buttons.primary-button type="submit">Import Affixes</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
