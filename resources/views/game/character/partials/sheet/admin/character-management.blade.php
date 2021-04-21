@if (auth()->user()->hasRole('Admin'))
    <div class="mt-3">
        <x-cards.card-with-title title="Character management">
            @include('admin.character-modeling.partials.character-management')
        </x-cards.card-with-title>
    </div>
@endif
