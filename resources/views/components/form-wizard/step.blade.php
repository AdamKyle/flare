@props([
  'stepTitle' => 'Step 1',
])

<div
  class="flex-none p-6 text-gray-700 dark:text-gray-200"
  :style="`width: calc(100% / ${totalSteps})`"
>
  <h3 class="mb-6 text-lg font-semibold">{{ $stepTitle }}</h3>
  {{ $slot }}
</div>
