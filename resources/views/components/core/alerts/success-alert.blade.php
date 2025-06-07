@props([
    'title' => 'Oh Christ Child!',
    'icon' => 'fas fa-exclamation-triangle',
])

<div
    x-data="{ show: true }"
    x-show="show"
    class="flex w-full mx-auto justify-between items-center bg-green-100 relative text-green-700 py-3 px-3 rounded-md shadow-sm shadow-green-200 border-solid border-2 border-green-700 dark:bg-green-300 dark:text-green-700 dark:shadow-gray-900 dark:border-green-600 mb-5 mt-4"
    role="alert"
>
    <div>
        <p class="font-bold text-green-700 dark:text-green-800-800 mb-5">
            <i class="{{ $icon }}"></i>
            {{ $title }}
        </p>
        {{ $slot }}
    </div>
</div>
