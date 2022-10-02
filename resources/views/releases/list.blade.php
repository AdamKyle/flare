@extends('layouts.releases-layout')

@section('content')
    <x-core.layout.smaller-container>

        @forelse($releases as $release)
            <x-core.cards.card-with-title css="mb-5" title="Version: {{$release->version}}, {{$release->name}}, Published on: {{$release->created_at->format('M d Y')}}">
                <h3 class="mb-3 mt-2"></h3>
                <div class="prose dark:prose-dark max-w-7xl mb-20 dark:text-white">
                    @markdown($release->body)
                </div>
            </x-core.cards.card-with-title>
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
