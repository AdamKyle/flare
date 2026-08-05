@extends('layouts.app')

@section('content')
  <div class="mr-auto ml-auto w-full lg:w-3/4">
    <x-core.page.title
      title="Characters"
      route="{{route('game')}}"
      link="Game"
      color="primary"
    ></x-core.page.title>

    <x-core.tables.data-table
      :paginator="$paginator"
      :columns="$columns"
      :searchable="true"
      empty-message="No characters found."
    />
  </div>
@endsection
