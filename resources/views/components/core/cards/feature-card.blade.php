<x-core.cards.card-with-hover>
  <div
    class="flex h-full flex-col items-center justify-center space-y-4 md:flex-row md:space-y-0 md:space-x-4"
  >
    <div class="w-1/5 text-7xl">
      {{ $icon }}
    </div>
    <div class="w-4/5">
      <h5 class="mb-3 text-lg md:text-xl lg:text-2xl">
        {{ $title }}
      </h5>
      <div class="text-sm md:text-base lg:text-lg">
        {{ $slot }}
      </div>
    </div>
  </div>
</x-core.cards.card-with-hover>
