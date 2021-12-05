@extends('layouts.app')

@section('content')
    <div class="tw-mt-10 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
        <x-core.page-title
          title="{{$unit->name}}"
          route="{{url()->previous()}}"
          color="primary"
          link="Back"
        >
            @guest
            @else
                @if (auth()->user()->hasRole('Admin'))
                    <x-core.buttons.link-buttons.primary-button
                        href="{{route('units.edit', [
                            'gameUnit' => $unit->id
                        ])}}"
                        css="tw-ml-2"
                    >Edit</x-core.buttons.link-buttons.primary-button>
                @endif
            @endguest
        </x-core.page-title>

        <hr />
        <x-core.cards.card>
            @include('admin.kingdoms.units.partials.unit-attributes', [
                'unit' => $unit
            ])
        </x-core.cards.card>
    </div>
@endsection
