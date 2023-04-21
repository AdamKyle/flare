<x-core.cards.card-with-title css="mb-5"
                              title="Version: {{$release->version}}, {{$release->name}}, Published on: {{$release->created_at->format('M d Y')}}"
>
    <h3 class="mb-3 mt-2"></h3>
    <div class="prose dark:prose-dark max-w-7xl mb-20 dark:text-white">
        {!! $content !!}
    </div>
</x-core.cards.card-with-title>
