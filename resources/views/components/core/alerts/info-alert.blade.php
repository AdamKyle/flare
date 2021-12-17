
@props([
    'title' => 'Info',
    'icon'  => 'far fa-question-circle',
])

<div class="px-4 py-3 leading-normal bg-blue-100 rounded-md drop-shadow-sm mb-3" role="alert">
  <p class="font-bold mb-2 text-blue-700"><i class="{{$icon}}"></i> {{$title}}</p>

  {{$slot}}
</div>