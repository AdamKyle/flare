@extends('layouts.minimum-with-nav-bar')

@section('content')
   <div class="container keep-down">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="row page-titles">
                    <div class="col-md-12">
                        <h1 class="mt-2">Releases</h1>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-12 mt-3">
                        @forelse($releases as $release)
                            <x-cards.card-with-title title="Version: {{$release->version}}">
                                <h3 class="mb-3 mt-2">{{$release->name}}</h3>
                                @markdown($release->body)
                                <hr />
                                <a href="{{$release->url}}" class="float-right btn btn-primary btn-sm">Read More <i class="fas fa-external-link-alt"></i></a>
                            </x-cards.card-with-title>
                        @empty
                            <x-cards.card-with-title title="No Releases">
                                There hasn't been any releases yet. Please check back later.
                            </x-cards.card-with-title>
                        @endforelse

                        {{ $releases->links() }}
                    </div>
                </div>
            </div>
        </div>
   </div>
@endsection
