@extends('layouts.app')

@section('content')
    <div class="tw-mt-20 tw-mb-10 tw-w-full lg:tw-w-3/5 tw-m-auto">
        <div class="tw-m-auto">
            <x-core.page-title
                title="Surveys"
                route="{{route('home')}}"
                link="Home"
                color="success"
            >
                <x-core.buttons.link-buttons.primary-button
                    href="{{route('admin.survey-builder.create-survey')}}"
                    css="tw-ml-2"
                >
                    Create New Survey
                </x-core.buttons.link-buttons.primary-button>

                <x-core.buttons.link-buttons.primary-button
                    href="{{route('admin.survey-builder.export-data')}}"
                    css="tw-ml-2"
                >
                    <i class="fas fa-file-export"></i>
                    Export
                </x-core.buttons.link-buttons.primary-button>

                <x-core.buttons.link-buttons.primary-button
                    href="{{route('admin.survey-builder.import-data')}}"
                    css="tw-ml-2"
                >
                    <i class="fas fa-file-upload"></i>
                    Import
                </x-core.buttons.link-buttons.primary-button>
            </x-core.page-title>
        </div>
        @livewire('admin.survey.survey-list')
    </div>
@endsection
