@extends('layouts.app')

@section('content')
    @include('admin.skills.partials.skill-info', [
        'skill' => $skill
    ])
@endsection
