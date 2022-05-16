<div class="w-full md:w-2/4 m-auto">
    @if (session('success'))
        <div class="pb-10">
            <x-core.alerts.closable-success-alert title="Look at you go!" icon="far fa-grin">
                <p class="mt-3">{{ session('success') }}</p>
            </x-core.alerts.closable-success-alert>
        </div>
    @endif

    @if (session('error'))
        <div class="pb-10">
            <x-core.alerts.danger-alert title="Oh No!!" icon="far fa-sad-cry">
                <p class="mt-3">{{ session('error') }}</p>
            </x-core.alerts.danger-alert>
        </div>
    @endif

    @if ($errors->any())
        <div class="pb-10">
            <x-core.alerts.danger-alert title="Whoops!" icon="far fa-sad-cry">
                @foreach($errors->all() as $error)
                    <p class="mt-3">{{ $error }}</p>
                @endforeach
            </x-core.alerts.danger-alert>
        </div>
    @endif
</div>

