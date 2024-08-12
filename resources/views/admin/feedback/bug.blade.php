@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{$suggestion->title}}"
            buttons="true"
            backUrl="{{route('admin.feedback.bugs')}}"
        >
            <div class="grid grid-cols-2 gap-2">
            <dl>
                <dt>Affecting Platform:</dt>
                <dd>{{Str::title($suggestion->platform)}}</dd>
            </dl>

            <div class="prose dark:prose-dark dark:text-white">
                {{$suggestion->description}}
            </div>
            </div>
        </x-core.cards.card-with-title>

        <div class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>
        <h3>Attached Images</h3>
        <div class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"></div>

        <div class="md:w-1/2">
            <div class="grid grid-cols-2 gap-4">
                @forelse($suggestion->uploaded_image_paths as $filePath)
                    <div class="relative">
                        <img src="{{Storage::disk('suggestions-and-bugs')->url($filePath)}}" class="rounded-sm p-1 bg-white border max-w-full cursor-pointer glightbox" alt="image"/>
                    </div>
                @empty
                    No Images Attached
                @endforelse
            </div>

            <div class="mt-4 text-gray-700 dark:text-white italic text-xs text-center">
                Click/Tap me to make me larger.
            </div>
        </div>
    </x-core.layout.info-container>
@endsection
