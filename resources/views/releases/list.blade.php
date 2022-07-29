@extends('layouts.minimum-with-nav-bar')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="Releases"
            route="{{route('home')}}"
            color="success" link="Home"
        >
        </x-core.page-title>

        @forelse($releases as $release)
            <x-core.cards.card-with-title css="mb-5" title="Version: {{$release->version}}, {{$release->name}}, Published on: {{$release->created_at->format('M d Y')}}">
                <h3 class="mb-3 mt-2"></h3>
                <div class="prose dark:prose-dark max-w-7xl mb-20 dark:text-white">
                    @markdown($release->body)
                </div>
                <hr />
                <a href="{{$release->url}}" class="float-right btn btn-primary btn-sm">Read More <i class="fas fa-external-link-alt"></i></a>
            </x-core.cards.card-with-title>
        @empty
            <x-core.cards.card-with-title css="mb-5" title="No Releases">
                There hasn't been any releases yet. Please check back later.
            </x-core.cards.card-with-title>
        @endforelse

        {{ $releases->links() }}
    </x-core.layout.info-container>
@endsection
