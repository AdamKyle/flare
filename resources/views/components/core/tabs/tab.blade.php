@props([
    'active' => 'false',
    'id'     => 'default-tab',
    'href'   => '#first',
])

@php
  $cssClass = '';
  if ($active === 'true') {
    $cssClass = 'tw-bg-blue-500 tw-border-t tw-border-r tw-border-l tw--mb-px';
  }
@endphp
<li class="{{"tw-font-medium tw-px-4 tw-py-2 tw-rounded-t-sm " . $cssClass}}">
  <a id="{{$id}}" href="{{$href}}" class="{{$cssClass !== '' ? 'tw-text-white' : ''}}">{{$slot}}</a>
</li>