@extends('layouts.admin')

@section('content')
    @include(
        'information.partials.core-info-section',
        [
            'pageTitle' => $pageTitle,
            'pageId' => $pageId,
            'sections' => $sections,
        ]
)
@endsection
