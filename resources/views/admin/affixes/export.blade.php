@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.cards.card-with-title
            title="Export Affixes"
            buttons="true"
            backUrl="{{route('affixes.list')}}"
        >
            <form method="POST" action="{{ route('affixes.export-data') }}">
                @csrf
                <div class="mb-5">
                    <label class="label block mb-2" for="export_type">
                        Type to export
                    </label>
                    <select class="form-control" name="export_type">
                        <option value="">Please select</option>
                        @foreach ($types as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
                <x-core.buttons.primary-button type="submit">
                    Export
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </x-core.layout.info-container>
@endsection
