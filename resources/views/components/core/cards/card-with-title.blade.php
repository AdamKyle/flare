@props([
    'title' => 'Example',
    'route' => null,
    'css'   => '',
])

<div class="{{$css}}">
  @if (!is_null($route))
    <h2 class="tw-font-light"><a href={{$route}} {{$attributes}}>{{$title}}</a></h2>
  @else
    <h2 class="tw-font-light">{{$title}}</h2>
  @endif

  <div class="tw-bg-white tw-rounded-sm tw-drop-shadow-sm tw-p-6">
    {{$slot}}
  </div>
</div>