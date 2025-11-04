@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-form-wizard.container totalSteps="3" name="Wizard Example">
      <x-form-wizard.step stepTitle="Step 1">
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
      </x-form-wizard.step>
      <x-form-wizard.step stepTitle="Step 2">
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
      </x-form-wizard.step>
      <x-form-wizard.step stepTitle="Step 3">
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
      </x-form-wizard.step>
    </x-form-wizard.container>
  </x-core.layout.info-container>
@endsection

