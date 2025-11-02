@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.alerts.warning-alert
      title="What exactly are you looking for?"
      icon="fas fa-exclamation-triangle"
    >
      <p class="my-4">
        What ever it was child, it does not exist.
        <a href="/">Back home we go!</a>
      </p>
    </x-core.alerts.warning-alert>
  </x-core.layout.info-container>
@endsection
