@props([
  'formRoute' => '',
  'buttonTitle' => '',
])

<form action="{{ $formRoute }}" method="POST">
  @csrf

  <x-core.buttons.primary-button type="submit">
    {{ $buttonTitle }}
  </x-core.buttons.primary-button>
</form>
