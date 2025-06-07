@extends('layouts.minimum')

@section('content')
    <x-core.layout.info-container>
        <x-core.alerts.danger-alert title="Woah! What Just happened!">
            <p class="my-4">
                Seems the game crashed! This is odd. Hmmm, Better get The
                Creator on it!
            </p>
            <p class="my-4">
                Quick child! head to
                <a href="https://discord.gg/hcwdqJUerh">Discord</a>
                and bitch! Scream! Yell! Anything!
            </p>
            <p class="my-4">
                This really doesn't look good, does it? Why did we crash? No one
                knows... Yet!
            </p>
        </x-core.alerts.danger-alert>
    </x-core.layout.info-container>
@endsection
