@props([
    'active' => 'false',
    'id'     => 'default-tab',
    'href'   => '#first',
])

@php
  $cssClass = '';

  if ($active === 'true') {
    $cssClass = 'tw-border-t-sm tw-border-r tw-border-l tw--mb-px tw-bg-blue-500';
  }

@endphp
<li class="{{"tw-font-medium tw-px-4 tw-py-2 tw-rounded-t-sm tw-text-center " . $cssClass}}">
  <a id="{{$id}}" href="{{$href}}" class="{{$cssClass !== '' ? 'tw-text-white' : ''}}">{{$slot}}</a>
</li>