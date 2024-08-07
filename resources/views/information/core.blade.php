@extends('layouts.information')

@section('content')

    <x-core.layout.info-container>
        <form id="search-form" method="GET" action="{{ route('info.search') }}">
            @csrf

            <div class="mb-5 flex justify-content-center">
                <input id="info_search" type="text" class="form-control mr-2" name="info_search" placeholder="Search for (any) content">
                <x-core.buttons.primary-button type="submit">Search</x-core.buttons.primary-button>
            </div>
        </form>
    </x-core.layout.info-container>

    @include('information.partials.core-info-section', [
        'pageTitle' => $pageTitle,
        'pageId'    => $pageId,
        'sections'  => $sections,
    ])
@endsection

