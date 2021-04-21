@if (auth()->user()->hasRole('Admin'))
    <div class="alert alert-info mb-2">
        <p>Changing the equipment will be applied when testing our battles.</p>

        <p>At the end of every test, where we reset the character back to it's max level, the inventory will not be touched.</p>

        <p><strong>Note</strong>: You cannot assign gold, as these characters are for testing monsters and making sure things are balanced.</p>
        @include('admin.character-modeling.partials.inventory-reset-form', [
            'character' => $character
        ])
    </div>
@endif
