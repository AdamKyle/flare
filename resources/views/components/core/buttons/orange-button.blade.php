<button
    class="{{ 'hover:bg-orange-600 hover:drop-shadow-md dark:text-white hover:text-gray-300 bg-orange-500 ' . 'dark:bg-orange-600 text-white dark:hover:bg-orange-600 dark:hover:text-white font-semibold py-2 px-4 ' . 'rounded-sm drop-shadow-sm disabled:bg-orange-400 dark:disabled:bg-orange-400 ' . 'focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-200 dark:focus-visible:ring-white ' . 'focus-visible:ring-opacity-75' }}"
    {{ $attributes }}
>
    {{ $slot }}
</button>
