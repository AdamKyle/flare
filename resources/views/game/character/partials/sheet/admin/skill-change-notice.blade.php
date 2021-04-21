@if (auth()->user()->hasRole('Admin'))
    <div class="alert alert-info mb-2">
        Changing the level of skills will be applied to the tests. When the character is reset
        from the test, the skill values you set here will not be changed.
    </div>
@endif
