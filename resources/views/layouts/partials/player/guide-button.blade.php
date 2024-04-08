@php
    $showModal = false;

    if (Cache::has('user-show-guide-initial-message-' . auth()->user()->id)) {
        Cache::delete('user-show-guide-initial-message-' . auth()->user()->id);

        $showModal = true;
    }
@endphp
@if (auth()->user()->guide_enabled)
    <div id="guide-button" data-open-modal="{{$showModal ? 'true' : 'false'}}"></div>

    @push('scripts')
        @vite('resources/js/guide-quests-init.tsx')
    @endpush
@endif
