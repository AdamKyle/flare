@props([
  'totalSteps' => 1,
  'name',
  'homeRoute' => '/',
  'formAction' => '#',
  'modelId' => '0',
])
<form
  x-data="{
    currentStep: 1,
    totalSteps: {{ $totalSteps }},
    viewportHeight: 0,
    ro: null,

    slides() {
      return this.$refs.track ? Array.from(this.$refs.track.children) : [];
    },

    measure() {
      this.$nextTick(() => {
        const current = this.slides()[this.currentStep - 1];
        if (!current) return;
        // measure AFTER layout settles so transforms/media/fonts won't trick us
        requestAnimationFrame(() => {
          this.viewportHeight = current.getBoundingClientRect().height;
        });
      });
    }
  }"
  x-init="
    measure();
    $watch('currentStep', () => {
      if (ro) ro.disconnect();
      measure();
      const current = slides()[currentStep - 1];
      if (current && 'ResizeObserver' in window) {
        ro = new ResizeObserver(() => measure());
        ro.observe(current);
      }
    });
  "
  @resize.window="measure()"
  x-cloak
  class="my-4 flex items-center justify-center px-4"
  action="{{ $formAction }}"
  method="post"
>
  @csrf
  <input type="hidden" name="id" value="{{ $modelId }}" />
  <div class="w-full max-w-2xl rounded-xl bg-white shadow-lg dark:bg-gray-800">
    <header
      class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700"
    >
      <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
        {{ $name }}
      </h2>
      <a
        href="{{ $homeRoute }}"
        class="text-sm font-medium text-blue-600 hover:underline focus:outline-none dark:text-blue-400"
      >
        Home
      </a>
    </header>

    <div
      class="overflow-hidden transition-[height] duration-300 ease-in-out"
      :style="`height: ${viewportHeight}px`"
    >
      <div
        x-ref="track"
        class="flex items-start transition-transform duration-500 ease-in-out"
        :style="`width: ${ totalSteps * 100 }%; transform: translateX(-${ (currentStep - 1) * (100 / totalSteps) }%);`"
      >
        {!! $slot !!}
      </div>
    </div>

    <div
      class="flex items-center justify-between border-t border-gray-200 px-6 py-4 dark:border-gray-700"
    >
      <button
        type="button"
        @click="currentStep > 1 && currentStep--"
        :disabled="currentStep === 1"
        class="rounded bg-gray-200 px-4 py-2 text-gray-700 focus:outline-none disabled:opacity-50 dark:bg-gray-700 dark:text-gray-200"
      >
        Previous
      </button>
      <template x-if="currentStep < totalSteps">
        <button
          type="button"
          @click="currentStep++"
          class="rounded bg-blue-600 px-4 py-2 text-white focus:outline-none"
        >
          Next
        </button>
      </template>
      <template x-if="currentStep === totalSteps">
        <button
          type="submit"
          class="rounded bg-green-600 px-4 py-2 text-white focus:outline-none"
        >
          Submit
        </button>
      </template>
    </div>

    <div class="flex justify-center space-x-2 px-6 pb-6">
      <template x-for="i in totalSteps" :key="i">
        <button
          type="button"
          @click="currentStep = i"
          :class="currentStep === i ? 'w-3 h-3 bg-danube-600' : 'w-3 h-3 bg-gray-300 dark:bg-gray-600'"
          class="rounded-full transition-colors duration-300 focus:outline-none"
        ></button>
      </template>
    </div>
  </div>
</form>
