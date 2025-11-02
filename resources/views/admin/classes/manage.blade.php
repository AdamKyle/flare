@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.cards.card-with-title
      title="{{!is_null($class) ? 'Edit: ' . nl2br($class->name) : 'Create New Class'}}"
      buttons="true"
      backUrl="{{!is_null($class) ? route('classes.class', ['class' => $class->id]) : route('classes.list')}}"
    >
      <x-core.form-wizard.container
        action="{{route('classes.store')}}"
        modelId="{{!is_null($class) ? $class->id : 0}}"
        lastTab="tab-style-2-5"
      >
        <x-core.form-wizard.tabs>
          <x-core.form-wizard.tab
            target="tab-style-2-1"
            primaryTitle="Basic Info"
            secondaryTitle="Basic information about the class."
            isActive="true"
          />
          <x-core.form-wizard.tab
            target="tab-style-2-2"
            primaryTitle="Skill Info"
            secondaryTitle="Basic information about the class effects on skills."
          />
          <x-core.form-wizard.tab
            target="tab-style-2-3"
            primaryTitle="Class Rank Info"
            secondaryTitle="How players can unlock the class."
          />
        </x-core.form-wizard.tabs>
        <x-core.form-wizard.contents>
          <x-core.form-wizard.content target="tab-style-2-1" isOpen="true">
            <h3 class="mb-3">Basic Info</h3>
            <x-core.forms.input
              :model="$class"
              label="Name:"
              modelKey="name"
              name="name"
            />
            <x-core.forms.select
              :model="$class"
              label="To Hit Stat:"
              modelKey="to_hit_stat"
              name="to_hit_stat"
              :options="$stats"
            />
            <x-core.forms.select
              :model="$class"
              label="Damage Stat:"
              modelKey="damage_stat"
              name="damage_stat"
              :options="$stats"
            />
            <x-core.forms.input
              :model="$class"
              label="Strength Modifier:"
              modelKey="str_mod"
              name="str_mod"
            />
            <x-core.forms.input
              :model="$class"
              label="Dexterity Modifier:"
              modelKey="dex_mod"
              name="dex_mod"
            />
            <x-core.forms.input
              :model="$class"
              label="Intelligence Modifier:"
              modelKey="int_mod"
              name="int_mod"
            />
            <x-core.forms.input
              :model="$class"
              label="Agility Modifier:"
              modelKey="agi_mod"
              name="agi_mod"
            />
            <x-core.forms.input
              :model="$class"
              label="Charisma Modifier:"
              modelKey="chr_mod"
              name="chr_mod"
            />
            <x-core.forms.input
              :model="$class"
              label="Durability Modifier:"
              modelKey="dur_mod"
              name="dur_mod"
            />
            <x-core.forms.input
              :model="$class"
              label="Focus Modifier:"
              modelKey="focus_mod"
              name="focus_mod"
            />
            <x-core.forms.input
              :model="$class"
              label="Defence Modifier:"
              modelKey="defense_mod"
              name="defense_mod"
            />
          </x-core.form-wizard.content>

          <x-core.form-wizard.content target="tab-style-2-2">
            <h3 class="mb-3">Basic Skill Info</h3>
            <x-core.forms.input
              :model="$class"
              label="Accuracy Modifier:"
              modelKey="accuracy_mod"
              name="accuracy_mod"
            />
            <x-core.forms.input
              :model="$class"
              label="Dodge Modifier:"
              modelKey="dodge_mod"
              name="dodge_mod"
            />
            <x-core.forms.input
              :model="$class"
              label="Looting Modifier:"
              modelKey="looting_mod"
              name="looting_mod"
            />
          </x-core.form-wizard.content>

          <x-core.form-wizard.content target="tab-style-2-3">
            <h3 class="mb-3">Class Rank Info</h3>
            <x-core.forms.key-value-select
              :model="$class"
              label="Primary Class Required:"
              modelKey="primary_required_class_id"
              name="primary_required_class_id"
              :options="$classes"
            />
            <x-core.forms.key-value-select
              :model="$class"
              label="Secondary Class Required:"
              modelKey="secondary_required_class_id"
              name="secondary_required_class_id"
              :options="$classes"
            />
            <x-core.forms.input
              :model="$class"
              label="Primary Class Level Required:"
              modelKey="primary_required_class_level"
              name="primary_required_class_level"
            />
            <x-core.forms.input
              :model="$class"
              label="Secondary Class Level Required:"
              modelKey="secondary_required_class_level"
              name="secondary_required_class_level"
            />
          </x-core.form-wizard.content>
        </x-core.form-wizard.contents>
      </x-core.form-wizard.container>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
