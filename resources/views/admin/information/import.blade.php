@extends('layouts.app')

@section('content')

    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Information"
            buttons="true"
            backUrl="{{route('admin.info-management')}}"
        >
            <div class="mt-4 mb-4">
                <x-core.alerts.info-alert title="ATTN!">
                    Make sure to copy the backup images over as well so the images are linked properly.
                </x-core.alerts.info-alert>
            </div>
            <form class="mt-4" action="{{route('admin.info-management.import')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <x-core.forms.file-upload label="Info Json File" name="info_import" />
                <x-core.buttons.primary-button type="submit">Import Information</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
