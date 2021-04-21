@if (auth()->user()->hasRole('Admin'))
    <a href="#" class="btn btn-success btn-sm train-skill-btn mb-2 mt-1">Change</a>
    <a href="#" class="btn btn-danger btn-sm train-skill-btn mb-2 mt-1">Reset</a>
@endif
