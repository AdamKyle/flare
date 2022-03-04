@if (session('success'))
    <div class="alert alert-success alert-dismissible fade show mb-2 mt-2">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if (session('error'))
    <x-core.alerts.danger-alert title="Oh No!!">
        <p class="mt-3">{{ session('error') }}</p>
    </x-core.alerts.danger-alert>
@endif

@if ($errors->any())
    <x-core.alerts.danger-alert>
        @foreach($errors->all() as $error)
            <p class="mt-3">{{ $error }}</p>
        @endforeach
    </x-core.alerts.danger-alert>
@endif

