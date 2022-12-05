@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Class Specials"
            buttons="true"
            backUrl="{{route('class-specials.list')}}"
        >
            <form class="mt-4" action="{{route('class-specials.import')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-5">
                    <label class="label block mb-2" for="class_specials_import">Items File</label>
                    <input id="class_specials_import" type="file" class="form-control" name="class_specials_import" />
                </div>
                <x-core.buttons.primary-button type="submit">Import Class Specials</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
