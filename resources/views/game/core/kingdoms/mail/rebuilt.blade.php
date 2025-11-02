@extends(
  'flare.email.core_email',
  [
    'title' => 'Rebuilding Finished',
    'showBottomText' => true,
  ]
)

@section('content')
  <mj-column width="400px">
    <mj-text color="#637381">
      Hello {{ $user->character->name }}, a building: {{ $building->name }}
      has finished being rebuilt.
    </mj-text>

    <mj-text color="#637381">You can see the details below:</mj-text>

    <mj-table>
      <tr
        style="
          border-bottom: 1px solid #2d2424;
          text-align: left;
          padding: 15px 0;
        "
      >
        <th style="padding: 0 15px 0 0; color: #637381">Kingdom Name</th>
        <th style="padding: 0 0 0 15px; color: #637381">X Position</th>
        <th style="padding: 0 0 0 15px; color: #637381">Y Position</th>
        <th style="padding: 0 0 0 15px; color: #637381">Plane</th>
      </tr>
      <tr>
        <td style="padding: 0 15px 0 0; color: #637381">
          {{ $building->kingdom->name }}
        </td>
        <td style="padding: 0 0 0 15px; color: #637381">
          {{ $building->kingdom->x_position }}
        </td>
        <td style="padding: 0 0 0 15px; color: #637381">
          {{ $building->kingdom->y_position }}
        </td>
        <td style="padding: 0 0 0 15px; color: #637381">
          {{ $building->kingdom->gameMap->name }}
        </td>
      </tr>
    </mj-table>

    <mj-table>
      <tr
        style="
          border-bottom: 1px solid #2d2424;
          text-align: left;
          padding: 15px 0;
        "
      >
        <th style="padding: 0 15px 0 0; color: #637381">Building Name</th>
        <th style="padding: 0 0 0 15px; color: #637381">Current Durability</th>
        <th style="padding: 0 0 0 15px; color: #637381">Current Defence</th>
      </tr>
      <tr>
        <td style="padding: 0 15px 0 0; color: #637381">
          {{ $building->name }}
        </td>
        <td style="padding: 0 0 0 15px; color: #637381">
          {{ $building->current_durability }}
        </td>
        <td style="padding: 0 0 0 15px; color: #637381">
          {{ $building->current_defence }}
        </td>
      </tr>
    </mj-table>

    <mj-button
      background-color="#21A52C"
      align="center"
      color="#637381;"
      font-size="17px"
      font-weight="bold"
      href="{{ route('login') }}"
      width="300px"
    >
      Login
    </mj-button>
  </mj-column>
@endsection
