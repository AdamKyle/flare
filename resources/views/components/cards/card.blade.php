@props([
    'additionalClasses' => '',
])

<div class="card {{$additionalClasses}}">
    <div class="card-body">
        {{$slot}}
    </div>
</div>
