@extends('layouts.information', [
    'pageTitle' => 'Adventure'
])

@section('content')
    <div class="tw-w-full lg:tw-w-3/5 tw-m-auto tw-mt-20 tw-mb-10">
        @include('admin.adventures.partials.adventure', [
            'adventure' => $adventure,
            'customUrl' => route('info.page', [
                'pageName' => 'adventure'
            ]),
        ])
    </div>
@endsection
