@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page-title
            title="Item World"
            route="{{route('game')}}"
            color="primary" link="Game"
        >
        </x-core.page-title>

        <canvas id="canvas" width="640" height="480"></canvas>

        <script src="{{asset('wasm/flare-item-world.js')}}"></script>
        <script>
            window.addEventListener("load", async () => {
                await wasm_bindgen("{{asset('wasm/flare-item-world_bg.wasm')}}")
            });
        </script>

    </x-core.layout.info-container>
@endsection
