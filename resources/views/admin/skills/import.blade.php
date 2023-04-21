@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Skills"
            buttons="true"
            backUrl="{{route('skills.list')}}"
        >
            <form class="mt-4" action="{{route('skills.import-data')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-5">
                    <label class="label block mb-2" for="skills_import">Items File</label>
                    <input id="skills_import" type="file" class="form-control" name="skills_import" />
                </div>
                <x-core.buttons.primary-button type="submit">Import Skills</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
