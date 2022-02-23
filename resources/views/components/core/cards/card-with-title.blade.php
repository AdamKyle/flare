@props([
    'title' => 'Example',
    'route' => null,
    'css'   => '',
])

<div class="{{$css}}">
  @if (!is_null($route))
    <h2 class="font-light mb-3">
        <a href={{$route}} {{$attributes}}>{{$title}}</a>
    </h2>
  @else
    <h2 class="font-light mb-3">{{$title}}</h2>
  @endif

  <div class="bg-white rounded-md drop-shadow-md p-6 overflow-x-auto dark:bg-gray-800">
    {{$slot}}
  </div>
</div>
