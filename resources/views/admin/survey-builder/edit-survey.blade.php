@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Survey Builder"
            buttons="true"
            backUrl="{{route('admin.single.survey', ['survey' => $surveyId])}}"
        >
            <div id="survey-builder" data-survey-id="{{ $surveyId }}"></div>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
