@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Item Skills"
            buttons="true"
            backUrl="{{route('admin.items-skills.list')}}"
        >
            <form
                method="POST"
                action="{{ route('admin.items-skills.export') }}"
                class="mb-4 text-center"
            >
                @csrf
                <x-core.buttons.primary-button type="submit">
                    Export
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
