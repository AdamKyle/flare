@extends('layouts.app')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 p-4">
        <x-core.cards.card-with-title
            title="{{$foundBug->title}}"
            buttons="true"
            backUrl="{{route('admin.feedback.suggestions')}}"
        >
            <div class="space-y-4">
                <div class="bg-gray-100 dark:bg-gray-800 p-4 rounded-md">
                    <dl>
                        <dt class="font-semibold text-gray-700 dark:text-white">For Platform:</dt>
                        <dd class="text-gray-500 dark:text-gray-300">{{Str::title($foundBug->platform)}}</dd>
                    </dl>
                </div>

                <div class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>

                <div class="prose dark:prose-dark dark:text-white">
                    <h2>Description</h2>
                    <div class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>

                    {!! $renderedHtml !!}
                </div>
            </div>
        </x-core.cards.card-with-title>

        <div>
            <h3 class="text-lg font-semibold text-gray-700 dark:text-white">Attached Images</h3>
            <div class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>

            <div class="grid grid-cols-2 gap-4">
                @forelse($foundBug->uploaded_image_paths as $filePath)
                    <div class="relative">
                        <img src="{{Storage::disk('suggestions-and-bugs')->url($filePath)}}" class="rounded-sm p-1 bg-white border max-w-full cursor-pointer glightbox" alt="image"/>
                    </div>
                @empty
                    <p class="text-gray-500 dark:text-gray-300">No Images Attached</p>
                @endforelse
            </div>

            <div class="mt-4 text-gray-700 dark:text-white italic text-xs text-center">
                Click/Tap me to make me larger.
            </div>
        </div>
    </div>
@endsection
