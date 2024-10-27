@php
function calculateGridCols(int $numItems) {
    if ($numItems <= 1) {
        return 'auto'; // No grid, single column
    } else if ($numItems >= 3) {
        return '3'; // No grid, auto layout
    } else {
        return '2'; // Use a 3-column grid
    }
}
@endphp

<div class="w-full lg:w-3/4 mx-auto mt-20">
    <div class="text-center">
        <h2 class="mb-5 font-thin text-2xl lg:text-5xl dark:text-gray-300 text-gray-800">
            <i class="far fa-calendar-alt"></i>
            Current Events Running!
        </h2>
        <p class="mb-10 dark:text-gray-300 text-gray-800">
            Tlessa currently has some events running you might be interested in! Oh how exciting!
        </p>
    </div>

    <div class="relative">
        <div class="absolute inset-0 bg-gradient-to-br from-orange-500 via-red-500 to-pink-500 opacity-50 blur-lg pulse"></div>

        <div class="relative z-10 py-4">
            @if (count($scheduledEventsRunning) === 1)
                <div class="w-full md:w-2/5 mx-auto">
                    <div class="my-4 h-full">
                        @include('./welcome-partials/event-card', ['eventRunning' => $scheduledEventsRunning[0]])
                    </div>
                </div>
            @elseif (count($scheduledEventsRunning) === 2)
                <div class="w-full md:w-4/5 mx-auto grid md:grid-cols-2 gap-2 items-stretch">
                    @foreach ($scheduledEventsRunning as $runningEvent)
                        <div class="my-4 h-full">
                            @include('./welcome-partials/event-card', ['eventRunning' => $runningEvent])
                        </div>
                    @endforeach
                </div>
            @else
                <div class="grid md:grid-cols-{{ calculateGridCols(count($scheduledEventsRunning)) }} gap-2 items-stretch">
                    @foreach ($scheduledEventsRunning as $runningEvent)
                        <div class="my-4 h-full">
                            @include('./welcome-partials/event-card', ['eventRunning' => $runningEvent])
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
