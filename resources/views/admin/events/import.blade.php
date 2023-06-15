@extends('layouts.app')

@section('content')

    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Import Affixes"
            buttons="true"
            backUrl="{{route('admin.events')}}"
        >
            <div class="mt-4 mb-4">
                <x-core.alerts.info-alert title="Importing Tip">
                    <p className='my-4'>
                        <strong>Caution</strong> If the event is connected to a raid and the raid does not exist, the event will not be imported.
                    </p>
                </x-core.alerts.info-alert>
            </div>
            <form class="mt-4" action="{{route('admin.events.import')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-5">
                    <label class="label block mb-2" for="scheduled_events">Scheduled Events File</label>
                    <input id="scheduled_events" type="file" class="form-control" name="scheduled_events" />
                </div>
                <x-core.buttons.primary-button type="submit">Import Scheduled Events</x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
