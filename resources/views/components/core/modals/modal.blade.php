@props([
    "title" => null,
    "open" => false,
    "maxWidth" => "2xl",
    "id" => null,
])

@php
  $dialogId = $id ?: uniqid("modal-");
  $labelId = $dialogId . "-label";
  $panelWidth =
      [
          "sm" => "max-w-sm",
          "md" => "max-w-md",
          "lg" => "max-w-lg",
          "xl" => "max-w-xl",
          "2xl" => "max-w-2xl",
          "3xl" => "max-w-3xl",
          "full" => "max-w-full",
      ][$maxWidth] ?? "max-w-2xl";
@endphp

<div
  x-data="{ modalIsOpen: {{ $open ? "true" : "false" }} }"
  {{ $attributes->merge(["class" => ""]) }}
>
  @isset($trigger)
    <span class="block w-full" x-on:click="modalIsOpen = true">
      {{ $trigger }}
    </span>
  @endisset

  <div
    x-cloak
    x-show="modalIsOpen"
    x-transition.opacity.duration.200ms
    x-trap.inert.noscroll="modalIsOpen"
    x-on:keydown.esc.window="modalIsOpen = false"
    x-on:click.self="modalIsOpen = false"
    class="fixed inset-0 z-[999] flex items-end justify-center bg-neutral-900/35 p-4 pb-8 backdrop-blur-sm supports-[backdrop-filter]:bg-neutral-900/30 sm:items-center lg:p-8 dark:bg-black/60"
    role="dialog"
    aria-modal="true"
    @if ($title)
        aria-labelledby="{{ $labelId }}"
    @endif
  >
    <div
      x-show="modalIsOpen"
      x-transition:enter="transition delay-100 duration-200 ease-out motion-reduce:transition-opacity"
      x-transition:enter-start="scale-y-0 opacity-0"
      x-transition:enter-end="scale-y-100 opacity-100"
      class="{{ $panelWidth }} z-[2147483647] w-full overflow-hidden rounded-lg border border-neutral-200 bg-white text-neutral-800 shadow-xl outline-none dark:border-neutral-700 dark:bg-neutral-900 dark:text-neutral-100"
    >
      <div
        class="flex items-center justify-between border-b border-neutral-200 bg-neutral-50/60 p-4 dark:border-neutral-700 dark:bg-neutral-900/40"
      >
        @if ($title)
          <h3
            id="{{ $labelId }}"
            class="font-semibold tracking-wide text-neutral-900 dark:text-neutral-50"
          >
            {{ $title }}
          </h3>
        @endif

        <button
          x-on:click="modalIsOpen = false"
          aria-label="Close"
          class="rounded-md p-2 text-neutral-600 hover:bg-neutral-200/60 hover:text-neutral-900 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-black dark:text-neutral-300 dark:hover:bg-neutral-800/60 dark:hover:text-white dark:focus-visible:outline-white"
        >
          <i class="fas fa-times"></i>
        </button>
      </div>

      <div
        class="max-h-[350px] overflow-x-auto px-4 py-6 text-neutral-800 md:max-h-[500px] dark:text-neutral-100"
      >
        {{ $slot }}
      </div>

      @isset($footer)
        <div
          class="flex flex-col-reverse justify-between gap-2 border-t border-neutral-200 bg-neutral-50/60 p-4 sm:flex-row sm:items-center md:justify-end dark:border-neutral-700 dark:bg-neutral-900/40"
        >
          {{ $footer }}
        </div>
      @endisset
    </div>
  </div>
</div>
