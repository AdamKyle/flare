@extends('layouts.app')

@section('content')
    <div class="max-w-[32rem] lg:max-w-3/4 m-auto mt-20 mb-10">
        <div class="mb-6 text-left md:text-center px-4">
            <h1 class="w-3/4 md:w-full">
                {{ $questionLabel }}
            </h1>
        </div>

        <div class="prose dark:prose-invert mr-auto ml-auto max-w-5xl lg:max-w-7xl">
            @forelse ($responses as $response)
                <x-core.cards.card>
                    {!! $response !!}
                </x-core.cards.card>
            @empty
                <x-core.cards.card>
                    There seems to be no responses to this question.
                </x-core.cards.card>
            @endforelse
        </div>
    </div>
@endsection
