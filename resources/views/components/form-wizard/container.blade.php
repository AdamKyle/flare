<!-- resources/views/components/form-wizard/container.blade.php -->
@props([
    'totalSteps' => 1,
    'name',
    'homeRoute' => '/',
    'formAction' => '#',
    'modelId' => '0',
])
<form
  x-data="{ currentStep: 1, totalSteps: {{ $totalSteps }} }"
  x-cloak
  class="flex items-center justify-center px-4 my-4"
  action="{{ $formAction }}"
  method="post"
>
  @csrf
  <input type="hidden" name="id" value="{{ $modelId }}" />
  <div class="w-full max-w-2xl bg-white dark:bg-gray-800 rounded-xl shadow-lg">
    <header class="flex items-center justify-between px-6 py-4 border-b border-gray-200 dark:border-gray-700">
      <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">{{ $name }}</h2>
      <a href="{{ $homeRoute }}" class="text-sm font-medium text-blue-600 dark:text-blue-400 hover:underline focus:outline-none">Home</a>
    </header>
    <div class="overflow-hidden">
      <div
        class="flex transition-transform duration-500 ease-in-out"
        :style="`width: ${ totalSteps * 100 }%; transform: translateX(-${ (currentStep - 1) * (100 / totalSteps) }%);`"
      >
        {!! $slot !!}
      </div>
    </div>
    <div class="flex items-center justify-between px-6 py-4 border-t border-gray-200 dark:border-gray-700">
      <button type="button" @click="currentStep > 1 && currentStep--" :disabled="currentStep === 1" class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded disabled:opacity-50 focus:outline-none">Previous</button>
      <template x-if="currentStep < totalSteps">
        <button type="button" @click="currentStep++" class="px-4 py-2 bg-blue-600 text-white rounded focus:outline-none">Next</button>
      </template>
      <template x-if="currentStep === totalSteps">
        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded focus:outline-none">Submit</button>
      </template>
    </div>
    <div class="flex justify-center space-x-2 px-6 pb-6">
      <template x-for="i in totalSteps" :key="i">
        <button
          type="button"
          @click="currentStep = i"
          :class="currentStep === i ? 'w-3 h-3 bg-danube-600' : 'w-3 h-3 bg-gray-300 dark:bg-gray-600'"
          class="rounded-full focus:outline-none transition-colors duration-300"
        ></button>
      </template>
    </div>
  </div>
</form>
