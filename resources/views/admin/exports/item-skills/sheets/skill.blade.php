<table>
  <thead>
    <tr>
      <th>name</th>
      <th>description</th>
      <th>str_mod</th>
      <th>dex_mod</th>
      <th>dur_mod</th>
      <th>chr_mod</th>
      <th>focus_mod</th>
      <th>int_mod</th>
      <th>agi_mod</th>
      <th>base_damage_mod</th>
      <th>base_ac_mod</th>
      <th>base_healing_mod</th>
      <th>max_level</th>
      <th>total_kills_needed</th>
      <th>parent_id</th>
      <th>parent_level_needed</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($skills as $skill)
      <tr>
        <td>{{ $skill->name }}</td>
        <td>{{ $skill->description }}</td>
        <td>{{ $skill->str_mod }}</td>
        <td>{{ $skill->dex_mod }}</td>
        <td>{{ $skill->dur_mod }}</td>
        <td>{{ $skill->chr_mod }}</td>
        <td>{{ $skill->focus_mod }}</td>
        <td>{{ $skill->int_mod }}</td>
        <td>{{ $skill->agi_mod }}</td>
        <td>{{ $skill->base_damage_mod }}</td>
        <td>{{ $skill->base_ac_mod }}</td>
        <td>{{ $skill->base_healing_mod }}</td>
        <td>{{ $skill->max_level }}</td>
        <td>{{ $skill->total_kills_needed }}</td>
        <td>
          {{ ! is_null($skill->parent_id) ? $skill->parent->name : '' }}
        </td>
        <td>{{ $skill->parent_level_needed }}</td>
      </tr>
    @endforeach
  </tbody>
</table>
