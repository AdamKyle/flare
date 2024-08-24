@php
    $showModal = false;

@endphp
@if (auth()->user()->guide_enabled)
    <div id="guide-button" data-open-modal="{{'false'}}"></div>

    @push('scripts')
        @vite('resources/js/guide-quests-init.tsx')
    @endpush
@endif
