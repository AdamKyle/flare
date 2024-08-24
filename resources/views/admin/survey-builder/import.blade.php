@extends('layouts.app')

@section('content')

    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Surveys"
            buttons="true"
            backUrl="{{route('admin.info-management')}}"
        >
            <form class="mt-4" action="{{route('admin.survey-builder.import')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-5">
                    <label class="label block mb-2" for="info_import">Survey Json File</label>
                    <input id="info_import" type="file" class="form-control" name="info_import" />
                </div>
                <x-core.buttons.primary-button type="submit">Import Surveys</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
