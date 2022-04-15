@extends('layouts.app')

@section('content')
    @dump([
        'item'      => $item,
        'effects'   => $effects,
        'monster'   => $monster,
        'quest'     => $quest,
        'location'  => $location,
        'adventure' => $adventure,
        'skills'    => $skills,
        'skill'     => $skill,
    ])
@endsection
