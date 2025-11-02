@extends('layouts.information')

@section('content')
  @include('admin.class-specials.partials.class-special', ['classSpecial' => $classSpecial])
@endsection
