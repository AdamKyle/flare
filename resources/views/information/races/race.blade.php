@extends(
    'layouts.information',
    [
        'pageTitle' => 'Race',
    ]
)

@section('content')
    @php
        $raceImageUrl = $race->image_path
            ? Storage::disk('public')->url($race->image_path)
            : asset('character-images/knight-in-a-field.png');
    @endphp

    <div class="mt-5">
        <x-core.cards.card-with-title title="{{ $race->name }}" css="mt-5">
            <div class="-mx-2 mb-4 flex flex-wrap items-start">
                <div class="mb-4 w-full px-2 md:w-1/3">
                    <img
                        src="{{ $raceImageUrl }}"
                        alt="{{ $race->name }} portrait"
                        class="w-full rounded-lg object-cover"
                    />
                </div>
                <div class="mb-4 w-full px-2 md:w-2/3">
                    @if (! is_null($race->description))
                        <p class="my-2">{{ $race->description }}</p>
                    @else
                        <p class="my-2">No description has been provided for this race yet.</p>
                    @endif
                </div>
            </div>
        </x-core.cards.card-with-title>
    </div>
@endsection
