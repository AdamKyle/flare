@props([
    'title' => 'Example',
    'route' => null,
    'css' => '',
    'buttons' => 'false',
    'backUrl' => '#',
    'editUrl' => '#',
    'secondaryUrl' => '#',
    'secondaryLabel' => '',
])

<div class="w-full md:w-2/3 mx-auto px-6 sm:px-8 lg:px-12 mt-12 mb-8">
  <div class="bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded-2xl shadow-xl overflow-hidden {{ $css }}">
    <div class="flex items-center justify-between p-8 border-b border-gray-200 dark:border-gray-700">
      @if ($buttons != 'false')
        @if (! is_null($route))
          <h2 class="text-3xl font-medium">
            <a href="{{ $route }}" {{ $attributes }} class="text-gray-900 dark:text-gray-100 hover:underline focus:outline-none">
              {!! $title !!}
            </a>
          </h2>
        @else
          <h2 class="text-3xl font-medium text-gray-900 dark:text-gray-100">
            {!! $title !!}
          </h2>
        @endif

        <div class="flex items-center space-x-4">
          @if (auth()->user()?->hasRole('Admin') && $editUrl !== '#')
            <x-core.buttons.link-buttons.primary-button href="{{ $editUrl }}">
              Edit
            </x-core.buttons.link-buttons.primary-button>
          @endif

          @if (auth()->user()?->hasRole('Admin') && $secondaryUrl !== '#')
            <x-core.buttons.link-buttons.orange-button href="{{ $secondaryUrl }}">
              {{ $secondaryLabel }}
            </x-core.buttons.link-buttons.orange-button>
          @endif

          <x-core.buttons.link-buttons.success-button href="{{ $backUrl }}">
            Back
          </x-core.buttons.link-buttons.success-button>
        </div>
      @else
        @if (! is_null($route))
          <h2 class="text-3xl font-medium">
            <a href="{{ $route }}" {{ $attributes }} class="text-gray-900 dark:text-gray-100 hover:underline focus:outline-none">
              {{ $title }}
            </a>
          </h2>
        @else
          <h2 class="text-3xl font-medium text-gray-900 dark:text-gray-100">
            {{ $title }}
          </h2>
        @endif
      @endif
    </div>

    <div class="p-8 text-gray-700 dark:text-gray-300">
      {{ $slot }}
    </div>
  </div>
</div>
