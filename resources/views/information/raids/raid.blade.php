@extends('layouts.information')

@section('content')
    @include('admin.raids.partials.raid', ['raid' => $raid])
@endsection
