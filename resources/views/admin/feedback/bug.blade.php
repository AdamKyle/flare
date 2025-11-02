@extends('layouts.app')

@section('content')
  <div class="grid grid-cols-1 gap-6 p-4 md:grid-cols-2">
    <x-core.cards.card-with-title
      title="{{$foundBug->title}}"
      buttons="true"
      backUrl="{{route('admin.feedback.bugs')}}"
    >
      <div class="space-y-4">
        <div class="rounded-md bg-gray-100 p-4 dark:bg-gray-800">
          <dl>
            <dt class="font-semibold text-gray-700 dark:text-white">
              Affecting Platform:
            </dt>
            <dd class="text-gray-500 dark:text-gray-300">
              {{ Str::title($foundBug->platform) }}
            </dd>
          </dl>
        </div>

        <div
          class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
        ></div>

        <div class="prose dark:prose-dark dark:text-white">
          <h2>Description</h2>
          <x-core.separator.separator />

          {!! $renderedHtml !!}
        </div>
      </div>
    </x-core.cards.card-with-title>

    <div>
      <h3 class="text-lg font-semibold text-gray-700 dark:text-white">
        Attached Images
      </h3>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>

      <div class="grid grid-cols-2 gap-4">
        @forelse ($foundBug->uploaded_image_paths as $filePath)
          <div class="relative">
            <img
              src="{{ Storage::disk('suggestions-and-bugs')->url($filePath) }}"
              class="glightbox max-w-full cursor-pointer rounded-sm border bg-white p-1"
              alt="image"
            />
          </div>
        @empty
          <p class="text-gray-500 dark:text-gray-300">No Images Attached</p>
        @endforelse
      </div>

      <div
        class="mt-4 text-center text-xs text-gray-700 italic dark:text-white"
      >
        Click/Tap me to make me larger.
      </div>
    </div>
  </div>
@endsection
