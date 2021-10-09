@props([
    'useHr' => 'false',
    'ulCss' => ''
])

<div class="tw-rounded-sm tw-border tw-mx-auto mt-2">
  <ul id="tw-tabs" class="{{'tw-inline-flex tw-pt-2 tw-px-1 tw-w-full tw-border-b tw-list-none ' . $ulCss}}">
    {{$tabs}}
  </ul>
  @if($useHr === 'true')
  <hr />
  @endif
  <div id="tab-contents">
    {{$content}}
  </div>
</div>