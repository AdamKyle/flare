@props([
    'href' => '#',
    'css' => '',
])

<a
    class="{{
        'bg-fuchsia-600 dark:bg-fuchsia-700 text-white dark:text-white font-semibold
                          py-2 px-4 rounded-sm drop-shadow-sm hover:bg-fuchsia-700 hover:drop-shadow-md dark:hover:text-white hover:text-gray-300 ' . $css
    }}"
    href="{{ $href }}"
    {{ $attributes }}
>
    {{ $slot }}
</a>
