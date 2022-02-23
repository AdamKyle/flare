@extends('layouts.app')

@section('content')
    <div class="container justify-content-center">
        <x-core.alerts.warning-alert title="Hmm....Not here...">
            <p class="text-yellow-700 dark:text-gray-800 mb-5">Seems you are lost child! Let me guide you home.</p>
            <x-core.buttons.link-buttons.primary-button href="/">
                Take me home
            </x-core.buttons.link-buttons.primary-button>
        </x-core.alerts.warning-alert>
    </div>
@endsection
