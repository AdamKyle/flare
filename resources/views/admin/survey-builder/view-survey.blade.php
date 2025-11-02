@extends('layouts.app')

@section('content')
  <div class="m-auto mt-20 mb-10 w-full lg:w-3/5">
    <div class="m-auto">
      <x-core.page.title
        title="{{ $survey['title'] }}"
        route="{{ route('admin.surveys') }}"
        link="Back"
        color="success"
      >
        <x-core.buttons.link-buttons.primary-button
          href="{{ route('admin.edit.survey', ['survey' => $survey['id']]) }}"
          css="tw-ml-2"
        >
          Edit
        </x-core.buttons.link-buttons.primary-button>
      </x-core.page.title>
    </div>
    <x-core.cards.card>
      <p class="mb-6 text-lg">{{ $survey['description'] }}</p>

      @foreach ($survey['sections'] as $section)
        <div class="mb-8">
          <h2 class="mb-4 text-2xl font-semibold">
            {{ $section['title'] }}
          </h2>

          <p class="mb-6 text-lg">{{ $section['description'] }}</p>

          <div
            class="space-y-4 border-l-4 border-gray-300 pl-4 dark:border-gray-700"
          >
            @foreach ($section['input_types'] as $field)
              <div>
                @if ($field['type'] === 'text')
                  <label class="block text-lg font-medium">
                    {{ $field['label'] }}
                  </label>
                  <input
                    type="text"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-800"
                  />
                @elseif ($field['type'] === 'checkbox')
                  <label class="block text-lg font-medium">
                    {{ $field['label'] }}
                  </label>
                  @foreach ($field['options'] as $index => $option)
                    <div class="mt-1 flex items-center">
                      <input
                        id="checkbox-{{ $index }}"
                        type="checkbox"
                        class="h-4 w-4 rounded border-gray-300 text-indigo-600 dark:border-gray-700 dark:bg-gray-800"
                      />
                      <label for="checkbox-{{ $index }}" class="ml-2">
                        {{ $option }}
                      </label>
                    </div>
                  @endforeach
                @elseif ($field['type'] === 'radio')
                  <label class="block text-lg font-medium">
                    {{ $field['label'] }}
                  </label>
                  @foreach ($field['options'] as $index => $option)
                    <div class="mt-1 flex items-center">
                      <input
                        id="radio-{{ $index }}"
                        type="radio"
                        name="{{ $field['label'] }}"
                        class="h-4 w-4 border-gray-300 text-indigo-600 dark:border-gray-700 dark:bg-gray-800"
                      />
                      <label for="radio-{{ $index }}" class="ml-2">
                        {{ $option }}
                      </label>
                    </div>
                  @endforeach
                @elseif ($field['type'] === 'select')
                  <label class="block text-lg font-medium">
                    {{ $field['label'] }}
                  </label>
                  <select
                    class="mt-1 block w-full rounded-md border border-gray-300 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                  >
                    @foreach ($field['options'] as $index => $option)
                      <option value="{{ $option }}">
                        {{ $option }}
                      </option>
                    @endforeach
                  </select>
                @elseif ($field['type'] === 'markdown')
                  <label class="block text-lg font-medium">
                    {{ $field['label'] }}
                  </label>
                  <textarea
                    class="mt-1 block w-full rounded-md border border-gray-300 p-6 placeholder-gray-400 shadow-sm dark:border-gray-700 dark:bg-gray-800 dark:placeholder-gray-600"
                    rows="3"
                    placeholder="This will be a Markdown component in the game."
                  ></textarea>
                @endif
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
    </x-core.cards.card>
  </div>
@endsection
