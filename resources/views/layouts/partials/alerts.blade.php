@if (session('success') || session('error'))
    <div class="mt-8 flex flex-col items-center space-y-6">
        @if (session('success'))
            <div class="w-2/3 px-4">
                <x-core.alerts.closable-success-alert title="Look at you go!" icon="far fa-grin">
                    <p class="mt-3">{{ session('success') }}</p>
                </x-core.alerts.closable-success-alert>
            </div>
        @endif

        @if (session('error'))
            <div class="w-2/3 px-4">
                <x-core.alerts.closable-danger-alert title="Oh No!!" icon="far fa-sad-cry">
                    <p class="mt-3">{{ session('error') }}</p>
                </x-core.alerts.closable-danger-alert>
            </div>
        @endif
    </div>
@endif
