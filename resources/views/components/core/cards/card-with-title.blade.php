@props([
    'title' => 'Example',
    'route' => null,
    'css'   => '',
])

<div class="{{$css}}">
  @if (!is_null($route))
    <h2 class="font-light"><a href={{$route}} {{$attributes}}>{{$title}}</a></h2>
  @else
    <h2 class="font-light">{{$title}}</h2>
  @endif

  <div class="bg-white rounded-sm drop-shadow-sm p-6 overflow-x-auto">
    {{$slot}}
  </div>
</div>