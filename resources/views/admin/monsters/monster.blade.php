@extends('layouts.app')

@section('content')
  <div class="w-full  mx-auto md:w-2/3 px-4">
      @include('admin.monsters.partials.monster', ['monster' => $monster, 'quest' => $quest])
  </div>
@endsection
