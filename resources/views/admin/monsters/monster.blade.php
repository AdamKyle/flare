@extends('layouts.app')

@section('content')
  <div class="container-fluid">
    <div class="justify-content-center container">
      @include('admin.monsters.partials.monster', ['monster' => $monster, 'quest' => $quest])
    </div>
  </div>
@endsection
