@extends('layouts.app')


@section('content')
    <div class="mb-5 text-center lg:mt-10">
        <h1 class="mb-5 text-4xl font-thin text-gray-800 dark:text-gray-300">Who's Playing Tlessa?</h1>
        <p class="mb-2 italic text-gray-800 dark:text-gray-300">Sometimes we might be slow, sometimes we might be busy, who ever could be online?</p>
    </div>

@endsection

@push('scripts')
    @vite('resources/js/online-character-stats-component.ts')
@endpush
