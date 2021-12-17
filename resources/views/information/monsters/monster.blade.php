@extends('layouts.information')

@section('content')
    <div class="w-full lg:w-3/5 m-auto mt-20 mb-10">
        @include('admin.monsters.partials.monster', [
            'monster' => $monster,
        ])
    </div>
@endsection
