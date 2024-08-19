@extends('layouts.app')



@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Survey Stats"
            buttons="true"
            backUrl="{{route('home')}}"
        >
            <h1>{{$survey['title']}}</h1>
            <p class="mb-6 text-lg">{{ $survey['description'] }}</p>
            <div class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
            <p class="my-4"><strong>Total player percentage who completed the survey:</strong> {{$characterWhoCompleted * 100}}%</p>
            <x-core.alerts.info-alert title="Quick note">
                Scroll down to see the responses of the server. At the bottom of the survey you will find The Creators response to the survey results.
            </x-core.alerts.info-alert>
            <div class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-2"></div>
            @foreach ($survey['sections'] as $section)
                <div class="mb-8">
                    <h2 class="text-2xl font-semibold mb-4">{{ $section['title'] }}</h2>

                    <p class="mb-6 text-lg">{{ $section['description'] }}</p>

                    <div class="space-y-4 pl-4 border-l-4 border-gray-300 dark:border-gray-700">
                        @foreach ($section['input_types'] as $field)
                            <div>
                                @if ($field['type'] === 'text')
                                    <label class="block text-lg font-medium">{{ $field['label'] }}</label>
                                    <input type="text" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm dark:bg-gray-800 dark:border-gray-700">
                                @elseif ($field['type'] === 'checkbox')
                                    <label class="block text-lg font-medium">{{ $field['label'] }}</label>
                                    @foreach ($field['options'] as $index => $option)
                                        <div class="flex items-center mt-1">
                                            <input disabled id="checkbox-{{ $index }}" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded dark:bg-gray-800 dark:border-gray-700">
                                            <label for="checkbox-{{ $index }}" class="ml-2">{{ $option }} <strong>({{ isset($field['value_percentage'][$option]) ? $field['value_percentage'][$option] : '0%' }})</strong></label>
                                        </div>
                                    @endforeach
                                @elseif ($field['type'] === 'radio')
                                    <label class="block text-lg font-medium">{{ $field['label'] }}</label>
                                    @foreach ($field['options'] as $index => $option)
                                        <div class="flex items-center mt-1">
                                            <input disabled id="radio-{{ $index }}" type="radio" name="{{ $field['label'] }}" class="h-4 w-4 text-indigo-600 border-gray-300 dark:bg-gray-800 dark:border-gray-700">
                                            <label for="radio-{{ $index }}" class="ml-2">{{ $option }} <strong>({{isset($field['value_percentage'][$option]) ? $field['value_percentage'][$option] : '0%'}})</strong></label>
                                        </div>
                                    @endforeach
                                @elseif ($field['type'] === 'select')
                                    <label class="block text-lg font-medium">{{ $field['label'] }}</label>
                                    <select disabled class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300">
                                        @foreach ($field['options'] as $index => $option)
                                            <option value="{{ $option }}">{{ $option }} ({{ $field['value_percentage'][$option] }})</option>
                                        @endforeach
                                    </select>
                                @elseif ($field['type'] === 'markdown')
                                    <label class="block text-lg font-medium">{{ $field['label'] }}</label>
                                    <form class="mt-4" action="{{route('survey.question-response', ['surveySnapshot' => $surveySnapShotId])}}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="survey_question" value="{{$field['label']}}" />
                                        <x-core.buttons.primary-button type="submit">See all responses</x-core.buttons.primary-button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </x-core.cards.card-with-title>

    </x-core.layout.info-container>
@endsection
