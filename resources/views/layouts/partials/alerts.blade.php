
@if (session('success') || session('error') || $errors->any())
    <div class="container flex items-center justify-center">
        @if (session('success'))
            <div class="pb-10 w-full md:w-1/2 xl:w-1/3">
                <x-core.alerts.closable-success-alert title="Look at you go!" icon="far fa-grin">
                    <p class="mt-3">{{ session('success') }}</p>
                </x-core.alerts.closable-success-alert>
            </div>
        @endif

        @if (session('error'))
            <div class="pb-10 w-full md:w-1/2 xl:w-1/3">
                <x-core.alerts.closable-danger-alert title="Oh No!!" icon="far fa-sad-cry">
                    <p class="mt-3">{{ session('error') }}</p>
                </x-core.alerts.closable-danger-alert>
            </div>
        @endif

        @if ($errors->any())
            <div class="pb-10 w-full md:w-1/2 xl:w-1/3">
                <x-core.alerts.closable-danger-alert title="Whoops!" icon="far fa-sad-cry">
                    @foreach($errors->all() as $error)
                        <p class="mt-3">{{ $error }}</p>
                    @endforeach
                </x-core.alerts.closable-danger-alert>
            </div>
        @endif
    </div>
@endif

