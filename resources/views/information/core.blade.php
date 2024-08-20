@extends('layouts.information')

@section('content')

    <div>

        <div class="max-w-3/5 flex justify-center">

            <form id="search-form" method="GET" action="{{ route('info.search') }}" class="w-3/5">
                @csrf

                <div class="mb-5 flex justify-content-center">
                    <input id="info_search" type="text" class="form-control mr-2" name="info_search" placeholder="Search for (any) content">
                    <x-core.buttons.primary-button type="submit">Search</x-core.buttons.primary-button>
                </div>
            </form>
        </div>

        @include('information.partials.core-info-section', [
            'pageTitle' => $pageTitle,
            'pageId'    => $pageId,
            'sections'  => $sections,
        ])
    </div>
@endsection

