@extends('layouts.information')

@section('content')

    <x-core.layout.info-container>
        <form id="search-form" method="GET" action="{{ route('info.search') }}">
            <div class="mb-5 flex justify-content-center">
                <input id="info-search" type="text" class="form-control mr-2" name="info-search" placeholder="Search for (any) content">
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

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var form  = document.getElementById('search-form');
            var input = form.querySelector('input[name="info-search"]');

            input.addEventListener('keypress', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    form.submit();
                }
            });
        });
    </script>
@endpush
