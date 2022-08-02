<x-core.cards.card css="mt-5 m-auto">
  <div class="row pb-2">
    <x-data-tables.per-page wire:model="perPage" />
    <x-data-tables.search wire:model="search" />
  </div>
  <x-data-tables.table :collection="$skills">
    <x-data-tables.header>
      <x-data-tables.header-row
        wire:click.prevent="sortBy('name')"
        header-text="Name"
        sort-by="{{$sortBy}}"
        sort-field="{{$sortField}}"
        field="name"
      />
      <x-data-tables.header-row
        wire:click.prevent="sortBy('max_level')"
        header-text="Max Level"
        sort-by="{{$sortBy}}"
        sort-field="{{$sortField}}"
        field="max_level"
      />
      <x-data-tables.header-row
        wire:click.prevent="sortBy('unlocks_at_level')"
        header-text="Unlocks at level"
        sort-by="{{$sortBy}}"
        sort-field="{{$sortField}}"
        field="unlocks_at_level"
      />
      <x-data-tables.header-row
        wire:click.prevent="sortBy('parent_skill_id')"
        header-text="Parent Skill"
        sort-by="{{$sortBy}}"
        sort-field="{{$sortField}}"
        field="parent_skill_id"
      />
      <x-data-tables.header-row
        wire:click.prevent="sortBy('parent_skill_id')"
        header-text="Unlocks Skill"
        sort-by="{{$sortBy}}"
        sort-field="{{$sortField}}"
        field="parent_skill_id"
      />
      @auth
        @if (auth()->user()->hasRole('Admin'))
          <x-data-tables.header-row>
            Actions_2
          </x-data-tables.header-row>
        @endif
      @endauth
    </x-data-tables.header>
    <x-data-tables.body>
      @forelse($skills as $skill)
        <tr>
          <td>
            @guest
              <a href="{{route('info.page.passive.skill', ['passiveSkill' => $skill->id])}}">{{$skill->name}}</a>
            @else
              @if (auth()->user()->hasRole('Admin'))
                <a href="{{route('passive.skills.skill', [
                                            'passiveSkill' => $skill->id
                                        ])}}">{{$skill->name}}</a>
              @elseif (isset($character))
                <a href="{{route('view.character.passive.skill', ['passiveSkill' => $skill->id, 'character' => $characterId])}}">{{$skill->name}}</a>
              @else
                <a href="{{route('info.page.passive.skill', ['passiveSkill' => $skill])}}">{{$skill->name}}</a>
              @endif
            @endguest
          </td>
          <td>{{$skill->max_level}}</td>
          @if (!is_null($skill->unlocks_at_level))
            <td>{{$skill->unlocks_at_level}}</td>
          @else
            <td>Does not unlock or is already unlocked.</td>
          @endif
          @if (!is_null($skill->parent_skill_id))
            <td>{{$skill->parent->name}}</td>
          @else
            <td>Does not have, or is already a parent skill.</td>
          @endif
          @if ($skill->childSkills->isNotEmpty())
            <td>{{implode(', ', $skill->childSkills->pluck('name')->toArray())}}</td>
          @else
            <td>Does not have additional skills.</td>
          @endif
          @guest
          @elseif (auth()->user()->hasRole('Admin'))
            <td>
              <a href="{{route('passive.skill.edit', [
                      'passiveSkill' => $skill->id,
              ])}}" class="btn btn-primary mt-2">Edit</a>
            </td>
          @endguest
        </tr>
      @empty
        @guest
          <x-data-tables.no-results colspan="3" />
        @elseif (auth()->user()->hasRole('Admin'))
          <x-data-tables.no-results colspan="4" />
        @endguest

      @endforelse
    </x-data-tables.body>
  </x-data-tables.table>
</x-core.cards.card>
