@extends('layouts.information', [
    'pageTitle' => null
])

@section('content')
    <div class="mt-5">
        @include('admin.classes.class', [
            'class' => $class,
            'customClass' => 'mt-5'
        ])
    </div>
@endsection
