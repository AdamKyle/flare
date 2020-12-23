@props([
    'title' => 'Example',
    'route' => null
])

@if (!is_null($route))
    <h4><a href={{$route}} {{$attributes}}>{{$title}}</a></h4>
@else
    <h4>{{$title}}</h4>
@endif

<div class="card">
    <div class="card-body">
        {{$slot}}
    </div>
</div>