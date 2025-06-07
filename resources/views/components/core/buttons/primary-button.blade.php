@props([
    'attributes' => '',
    'css' => '',
])

<button
    class="{{
        'hover:bg-blue-700 hover:drop-shadow-md hover:text-gray-300 bg-blue-600 dark:bg-blue-700 text-white dark:hover:text-white font-semibold
                          py-2 px-4 rounded-sm drop-shadow-sm disabled:opacity-75 disabled:cursor-not-allowed ' . $css
    }}"
    {{ $attributes }}
>
    {{ $slot }}
</button>
