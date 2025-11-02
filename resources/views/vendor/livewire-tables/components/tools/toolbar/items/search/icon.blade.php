@props([
  'searchIcon',
  'searchIconClasses',
  'searchIconOtherAttributes',
])
<div
  class="pointer-events-none relative inset-y-0 left-6 inline-flex items-center"
>
  @svg($searchIcon, $searchIconClasses, $searchIconOtherAttributes)
</div>
