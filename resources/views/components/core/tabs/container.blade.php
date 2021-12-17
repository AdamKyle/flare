@props([
    'useHr'     => 'false',
    'ulCss'     => '',
    'tabsId'    => 'tabs',
    'contentId' => 'tab-contents',
])

<div class="rounded-sm border mx-auto mt-2">
  <ul id="{{$tabsId}}" class="{{'inline-flex pt-2 px-1 w-full border-b list-none overflow-y-hidden overflow-x-auto ' . $ulCss}}">
    {{$tabs}}
  </ul>
  @if($useHr === 'true')
  <hr />
  @endif
  <div id="{{$contentId}}">
    {{$content}}
  </div>
</div>