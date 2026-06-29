@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{ $locationTemplate->name }}"
            buttons="true"
            :back-url="route('admin.location-templates.list')"
            :edit-url="route('admin.location-templates.edit', ['locationTemplate' => $locationTemplate])"
        >
            <dl class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-gray-100">Name</dt>
                    <dd>{{ $locationTemplate->name }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-gray-100">Type</dt>
                    <dd>{{ ucfirst($locationTemplate->type) }}</dd>
                </div>
                <div class="md:col-span-2">
                    <dt class="font-semibold text-gray-900 dark:text-gray-100">Description</dt>
                    <dd>{{ $locationTemplate->description }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-gray-100">Is Port</dt>
                    <dd>{{ $locationTemplate->is_port ? 'Yes' : 'No' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-gray-100">Can Players Enter</dt>
                    <dd>{{ $locationTemplate->can_players_enter ? 'Yes' : 'No' }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-gray-100">Created At</dt>
                    <dd>{{ $locationTemplate->created_at }}</dd>
                </div>
                <div>
                    <dt class="font-semibold text-gray-900 dark:text-gray-100">Updated At</dt>
                    <dd>{{ $locationTemplate->updated_at }}</dd>
                </div>
            </dl>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
