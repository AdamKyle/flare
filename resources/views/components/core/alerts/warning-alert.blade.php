
@props([
    'title' => 'Warning',
    'icon'  => 'far fa-question-circle',
])

<div class="tw-px-4 tw-py-3 tw-leading-normal tw-bg-yellow-100 tw-rounded-md tw-drop-shadow-sm tw-mb-3" role="alert">
  <p class="font-bold tw-mb-2 tw-text-yellow-700"><i class="{{$icon}}"></i> {{$title}}</p>

  {{$slot}}
</div>