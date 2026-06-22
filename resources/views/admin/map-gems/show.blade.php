@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{ $gameMapGemParamter->name }}"
            buttons="true"
            :back-url="route('admin.map-gems.list')"
            :edit-url="route('admin.map-gems.edit', ['gameMapGemParamter' => $gameMapGemParamter])"
        >
            <div class="mb-6 flex flex-wrap items-center gap-2">
                <form method="POST" action="{{ route('admin.map-gems.roll', ['gameMapGemParamter' => $gameMapGemParamter]) }}">
                    @csrf
                    <x-core.buttons.primary-button type="submit">
                        {{ is_null($gameMapGemParamter->rolled_gem_id) ? 'Roll Gem' : 'Re-roll Gem' }}
                    </x-core.buttons.primary-button>
                </form>

                @if(! is_null($gameMapGemParamter->rolled_gem_id))
                    <x-core.buttons.link-buttons.primary-button href="{{ route('admin.map-gems.rolled', ['gameMapGemParamter' => $gameMapGemParamter]) }}">
                        View Rolled Stats
                    </x-core.buttons.link-buttons.primary-button>
                @endif
            </div>

            @include('admin.map-gems.partials.details')
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
