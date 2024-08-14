@extends('layouts.app')

@section('content')
    <div class="mt-20 mb-10 w-full lg:w-3/5 m-auto">
        <div class="m-auto">
            <x-core.page-title
                title="{{ $survey['title'] }}"
                route="{{ url()->previous() }}"
                link="Back"
                color="success"
            >
            </x-core.page-title>
        </div>
        <x-core.cards.card>
            <p class="mb-6 text-lg">{{ $survey['description'] }}</p>

            @foreach ($survey['sections'] as $section)
                <div class="mb-8">
                    <h2 class="text-2xl font-semibold mb-4">{{ $section['title'] }}</h2>

                    <div class="space-y-4 pl-4 border-l-4 border-gray-300 dark:border-gray-700">
                        @foreach ($section['input_types'] as $field)
                            <div>
                                @if ($field['type'] === 'text')
                                    <label class="block text-lg font-medium">{{ $field['label'] }}</label>
                                    <input type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-800 dark:border-gray-700">
                                @elseif ($field['type'] === 'textarea')
                                    <label class="block text-lg font-medium">{{ $field['label'] }}</label>
                                    <textarea class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm dark:bg-gray-800 dark:border-gray-700 placeholder-gray-400 dark:placeholder-gray-600" rows="3" placeholder="This will be a Markdown component in the game."></textarea>
                                @elseif ($field['type'] === 'checkbox')
                                    @foreach ($field['options'] as $index => $option)
                                        <div class="flex items-center mt-1">
                                            <input id="checkbox-{{ $index }}" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded dark:bg-gray-800 dark:border-gray-700">
                                            <label for="checkbox-{{ $index }}" class="ml-2">{{ $option }}</label>
                                        </div>
                                    @endforeach
                                @elseif ($field['type'] === 'radio')
                                    @foreach ($field['options'] as $index => $option)
                                        <div class="flex items-center mt-1">
                                            <input id="radio-{{ $index }}" type="radio" name="{{ $field['label'] }}" class="h-4 w-4 text-indigo-600 border-gray-300 dark:bg-gray-800 dark:border-gray-700">
                                            <label for="radio-{{ $index }}" class="ml-2">{{ $option }}</label>
                                        </div>
                                    @endforeach
                                @elseif ($field['type'] === 'markdown')
                                    <label class="block text-lg font-medium">{{ $field['label'] }}</label>
                                    <textarea class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm dark:bg-gray-800 dark:border-gray-700 placeholder-gray-400 dark:placeholder-gray-600 p-6" rows="3" placeholder="This will be a Markdown component in the game."></textarea>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </x-core.cards.card>
    </div>
@endsection
