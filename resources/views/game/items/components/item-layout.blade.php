@php
  $backUrl = route('items.list');

  if (is_null(auth()->user())) {
    $backUrl = url()->previous();
  } else {
    if (
      ! auth()
        ->user()
        ->hasRole('Admin')
    ) {
      $backUrl = route('game.shop.buy', ['character' => auth()->user()->character->id]);
    }
  }
@endphp

<div class="my-8 flex items-center justify-between">
  <h2 class="font-light text-2xl leading-tight flex items-center h-10 relative top-1">
    <x-item-display-color :item="$item" />
  </h2>

  <div class="relative mb-0">
    <div class="mt-0 flex items-center">
      @auth
        @if (auth()->user()->hasRole('Admin'))
          <x-core.buttons.link-buttons.success-button
            href="{{ $backUrl }}"
            css="tw-ml-2"
          >
            Back
          </x-core.buttons.link-buttons.success-button>
          <x-core.buttons.link-buttons.primary-button
            href="{{ route('items.edit', ['item' => $item->id]) }}"
            css="tw-ml-2"
          >
            Edit Item
          </x-core.buttons.link-buttons.primary-button>
        @endif
      @endauth
    </div>
  </div>
</div>


<x-core.separator.separator />

@if ($item->type === 'alchemy' && $item->damages_kingdoms)
  @include('game.items.components.item-kingdom-details')
@elseif ($item->type === 'alchemy' && ! is_null($item->holy_level))
  @include('game.items.components.item-holy-oil-details')
@elseif ($item->type === 'alchemy' && $item->usable)
  @include('game.items.components.item-usable')
@else
  @include('game.items.components.item-details')
@endif
