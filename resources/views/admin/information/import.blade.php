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
                    Make sure to copy the backup images over as well so the
                    images are linked properly.
                </x-core.alerts.info-alert>
            </div>
            <form
                class="mt-4"
                action="{{ route('admin.info-management.import') }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf
                <div class="mb-5">
                    <label class="label block mb-2" for="info_import">
                        Info Json File
                    </label>
                    <input
                        id="info_import"
                        type="file"
                        class="form-control"
                        name="info_import"
                    />
                </div>
                <x-core.buttons.primary-button type="submit">
                    Import Information
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
