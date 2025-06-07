@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Races"
            buttons="true"
            backUrl="{{route('races.list')}}"
        >
            <form
                class="mt-4"
                action="{{ route('races.import') }}"
                method="POST"
                enctype="multipart/form-data"
            >
                @csrf
                <div class="mb-5">
                    <label class="label block mb-2" for="races_import">
                        Races File
                    </label>
                    <input
                        id="races_import"
                        type="file"
                        class="form-control"
                        name="races_import"
                    />
                </div>
                <x-core.buttons.primary-button type="submit">
                    Import Races
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
