@extends('layouts.minimum')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title css="mb-5" title="Down for Maintenance">
      <p class="my-4">
        Oh hello there kind stranger. How are you today? I hope you are doing
        fantastic.
      </p>

      <p class="my-4">
        I sincerely apologize, however, Tlessa had to go into maintenance mode.
        The Creator is busy updating the game with new and fantastic updates
        that he is excited to share with you!
      </p>

      <p class="my-4">
        If you would like, head over to
        <a href="https://discord.gg/hcwdqJUerh">Discord</a>
        and check the #announcements or the #releases section for more
        information!
      </p>

      <p class="my-4">
        Don't worry child the game will be back up soon, and you will be able to
        slay all the monsters and gather all the treasure's your little heart
        desires.
      </p>

      <p class="my-4">Sincerely,</p>

      <p class="my-4">The Poet</p>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
