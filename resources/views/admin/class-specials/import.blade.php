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
                <x-core.forms.file-upload label="Items File" name="class_specials_import" />
                <x-core.buttons.primary-button type="submit">Import Class Specials</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
