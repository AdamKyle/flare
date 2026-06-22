@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{ $gameLocationGemParamter->name }}"
            buttons="true"
            :back-url="route('admin.location-gems.list')"
            :edit-url="route('admin.location-gems.edit', ['gameLocationGemParamter' => $gameLocationGemParamter])"
        >
            <div class="mb-6 flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('admin.location-gems.roll', ['gameLocationGemParamter' => $gameLocationGemParamter]) }}">
                    @csrf
                    <x-core.buttons.primary-button type="submit">
                        {{ is_null($gameLocationGemParamter->rolled_gem_id) ? 'Roll Gem' : 'Re-roll Gem' }}
                    </x-core.buttons.primary-button>
                </form>

                @if(! is_null($gameLocationGemParamter->rolled_gem_id))
                    <x-core.buttons.link-buttons.primary-button href="{{ route('admin.location-gems.rolled', ['gameLocationGemParamter' => $gameLocationGemParamter]) }}">
                        View Rolled Stats
                    </x-core.buttons.link-buttons.primary-button>
                @endif
            </div>

            @include('admin.location-gems.partials.details')
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
