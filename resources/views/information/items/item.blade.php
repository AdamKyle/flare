@extends('layouts.information')

@section('content')
    <div class="mt-20 mb-10 w-full lg:w-3/5 m-auto">
        <x-core.page.title-slot
            route="{{url()->previous()}}"
            link="Back"
            color="primary"
        >
            <x-item-display-color :item="$item" />
        </x-core.page.title-slot>

        @include(
            'game.items.item',
            [
                'item' => $item,
            ]
        )
    </div>
@endsection
