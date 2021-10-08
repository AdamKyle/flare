@props([
    'title'             => 'Example',
    'route'             => null,
    'additionalClasses' => '',
])

@if (!is_null($route))
  <h2 class="tw-font-light"><a href={{$route}} {{$attributes}}>{{$title}}</a></h2>
@else
  <h2 class="tw-font-light">{{$title}}</h2>
@endif

<div class="tw-bg-white tw-rounded-md tw-drop-shadow-md tw-p-6">
  {{$slot}}
</div>