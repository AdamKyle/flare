@extends('layouts.admin')

@section('content')
    @include(
        'admin.skills.partials.skill-info',
        [
            'skill' => $skill,
        ]
)
@endsection
