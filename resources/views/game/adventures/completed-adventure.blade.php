@extends('layouts.app')

@section('content')
    @dump($adventureLog->rewards, $adventureLog->logs)
@endsection
