@props([
    'useHr'     => 'false',
    'ulCss'     => '',
    'tabsId'    => 'tw-tabs',
    'contentId' => 'tab-contents',
])

<div class="tw-rounded-sm tw-border tw-mx-auto mt-2">
  <ul id="{{$tabsId}}" class="{{'tw-inline-flex tw-pt-2 tw-px-1 tw-w-full tw-border-b tw-list-none tw-overflow-y-hidden tw-overflow-x-auto ' . $ulCss}}">
    {{$tabs}}
  </ul>
  @if($useHr === 'true')
  <hr />
  @endif
  <div id="{{$contentId}}">
    {{$content}}
  </div>
</div>