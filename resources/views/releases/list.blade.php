@extends('layouts.minimum-with-nav-bar')

@section('content')
   <div class="container mb-10">
       @forelse($releases as $release)
           <x-core.cards.card-with-title css="mb-5" title="Version: {{$release->version}}, {{$release->name}}">
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
   </div>
@endsection
