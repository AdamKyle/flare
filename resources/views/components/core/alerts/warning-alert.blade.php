@props([
    'title' => 'Warning',
    'icon' => 'far fa-question-circle',
])

<div
    x-data="{ show: true }"
    x-show="show"
    class="flex w-full mx-auto justify-between items-center bg-yellow-100 relative text-yellow-700 py-3 px-3 rounded-md shadow-sm shadow-red-200 border-solid border-2 border-yellow-400 dark:bg-yellow-200 dark:text-yellow-700 dark:shadow-gray-900 dark:border-yellow-500 mb-5"
    role="alert"
>
    <div>
        <p class="font-bold text-yellow-700 dark:text-yellow-800-800 mb-5">
            <i class="{{ $icon }}"></i>
            {{ $title }}
        </p>
        {{ $slot }}
    </div>
</div>
