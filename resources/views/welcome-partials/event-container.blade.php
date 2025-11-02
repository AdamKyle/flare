@php
  function calculateGridCols(int $numItems)
  {
    if ($numItems <= 1) {
      return 'auto';
    } elseif ($numItems >= 3) {
      return '3';
    } else {
      return '2';
    }
  }
@endphp

<div class="mx-auto mt-20 w-full lg:w-3/4">
  <div class="text-center">
    <h2
      class="mb-5 text-2xl font-thin text-gray-800 lg:text-5xl dark:text-gray-300"
    >
      <i class="far fa-calendar-alt"></i>
      Current Events Running!
    </h2>
    <p class="mb-10 text-gray-800 dark:text-gray-300">
      Tlessa currently has some events running you might be interested in! Oh
      how exciting!
    </p>
  </div>

  <div class="relative">
    <div
      class="pulse absolute inset-0 bg-gradient-to-br from-orange-500 via-red-500 to-pink-500 opacity-50 blur-lg"
    ></div>

    <div class="relative z-10 py-4">
      @if (count($scheduledEventsRunning) === 1)
        <div class="mx-auto w-full md:w-2/5">
          <div class="my-4 h-full">
            @include(
              './welcome-partials/event-card',
              [
                'eventRunning' => $scheduledEventsRunning[0],
              ]
            )
          </div>
        </div>
      @elseif (count($scheduledEventsRunning) === 2)
        <div
          class="mx-auto grid w-full items-stretch gap-2 md:w-4/5 md:grid-cols-2"
        >
          @foreach ($scheduledEventsRunning as $runningEvent)
            <div class="my-4 h-full">
              @include('./welcome-partials/event-card', ['eventRunning' => $runningEvent])
            </div>
          @endforeach
        </div>
      @else
        <div
          class="md:grid-cols-{{ calculateGridCols(count($scheduledEventsRunning)) }} grid items-stretch gap-2"
        >
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
