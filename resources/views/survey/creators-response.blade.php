@extends('layouts.app')

@section('content')
  <div class="m-auto mt-20 mb-10 max-w-[32rem] lg:max-w-3/4">
    <div class="mb-6 px-4 text-left md:text-center">
      <h1 class="w-3/4 md:w-full">The Creators Response</h1>
    </div>

    <div class="prose dark:prose-invert mr-auto ml-auto max-w-5xl lg:max-w-7xl">
      <x-core.cards.card>
        <p>
          No response has been given yet. It may take him a couple days to read
          through, gather his thoughts and formulate them here for you. Please
          check back soon!
        </p>
      </x-core.cards.card>
    </div>
  </div>
@endsection
