@extends('layouts.releases-layout')

@section('content')
    <x-core.layout.smaller-container>
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
    </x-core.layout.smaller-container>
@endsection
