@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Passives"
            buttons="true"
            backUrl="{{route('passive.skills.list')}}"
        >
            <form
                class="mt-4"
                action="{{ route('passive.skills.import-data') }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf
                <div class="mb-5">
                    <label class="label block mb-2" for="passives_import">
                        Passives File
                    </label>
                    <input
                        id="passives_import"
                        type="file"
                        class="form-control"
                        name="passives_import"
                    />
                </div>
                <x-core.buttons.primary-button type="submit">
                    Import Passives
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
