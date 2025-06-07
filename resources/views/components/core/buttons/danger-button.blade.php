@props([
    'attributes' => '',
    'css' => '',
])

<button
    class="{{
        'hover:bg-red-700 hover:drop-shadow-md hover:text-gray-300 bg-red-600 dark:bg-red-700 text-white dark:hover:text-white font-semibold
                          py-2 px-4 rounded-sm drop-shadow-sm ' . $css
    }}"
    {{ $attributes }}
>
    {{ $slot }}
</button>
