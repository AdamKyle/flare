@extends('layouts.admin')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="{{ !is_null($locationTemplate) ? 'Edit: ' . $locationTemplate->name : 'Create Location Template' }}"
            buttons="true"
            :back-url="!is_null($locationTemplate) ? route('admin.location-templates.show', ['locationTemplate' => $locationTemplate]) : route('admin.location-templates.list')"
        >
            <form method="POST" action="{{ route('admin.location-templates.store') }}">
                @csrf
                <input type="hidden" name="id" value="{{ !is_null($locationTemplate) ? $locationTemplate->id : 0 }}">

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <x-core.forms.input :model="$locationTemplate" label="Name" modelKey="name" name="name" required autocomplete="off" />
                    <x-core.forms.key-value-select :model="$locationTemplate" label="Type" modelKey="type" name="type" :options="$templateTypes" required />
                    <div class="md:col-span-2">
                        <x-core.forms.text-area :model="$locationTemplate" label="Description" modelKey="description" name="description" required />
                    </div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="can_players_enter" value="1" @checked(is_null($locationTemplate) || $locationTemplate->can_players_enter)>
                        <span>Can Players Enter</span>
                    </label>
                </div>

                <div class="mt-4 flex gap-2">
                    <x-core.buttons.primary-button type="submit">Save</x-core.buttons.primary-button>
                </div>
            </form>

            @if(!is_null($locationTemplate))
                <form method="POST" action="{{ route('admin.location-templates.delete', ['locationTemplate' => $locationTemplate]) }}" class="mt-4">
                    @csrf
                    <x-core.buttons.danger-button type="submit">Delete</x-core.buttons.danger-button>
                </form>
            @endif
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
