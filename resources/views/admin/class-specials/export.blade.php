@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Class Specials"
            buttons="true"
            backUrl="{{route('class-specials.list')}}"
        >
            <form
                method="POST"
                action="{{ route('class-specials.export') }}"
                class="text-center"
            >
                @csrf
                <x-core.buttons.primary-button type="submit">
                    Export
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
