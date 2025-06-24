@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        @forelse ($releases as $release)
            <x-release-note :releaseNote="$release" />
        @empty
            <x-core.cards.card-with-title css="mb-5" title="No Releases">
                There hasn't been any releases yet. Please check back later.
            </x-core.cards.card-with-title>
        @endforelse

        <div class="pb-5">
            {{ $releases->links() }}
        </div>
    </x-core.layout.info-container>
@endsection
