@extends('layouts.information')

@section('content')
  <div class="m-auto mt-20 mb-10 w-full lg:w-3/5">
    <x-core.page.title
      title="Back"
      route="{{url()->previous()}}"
      color="success"
      link="back"
    >
      <x-item-display-color :item="$item" />
    </x-core.page.title>

    @include(
      'game.items.item',
      [
        'item' => $item,
      ]
    )
  </div>
@endsection
