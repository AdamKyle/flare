@extends(
  'flare.email.core_email',
  [
    'title' => 'Units Recruited',
    'showBottomText' => true,
  ]
)

@section('content')
  <mj-column width="400px">
    <mj-text color="#637381">
      Hello {{ $user->character->name }}, your units have been recruited.
    </mj-text>

    <mj-text color="#637381">
      Below are the details of the recruitment process:
    </mj-text>

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
      </tr>
      <tr>
        <td style="padding: 0 15px 0 0; color: #637381">
          {{ $kingdom->name }}
        </td>
        <td style="padding: 0 0 0 15px; color: #637381">
          {{ $kingdom->x_position }}
        </td>
        <td style="padding: 0 0 0 15px; color: #637381">
          {{ $kingdom->y_position }}
        </td>
        <td style="padding: 0 0 0 15px; color: #637381">
          {{ $kingdom->gameMap->name }}
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
        <th style="padding: 0 15px 0 0; color: #637381">Unit Name</th>
        <th style="padding: 0 15px 0 0; color: #637381">Total Units</th>
      </tr>
      <tr>
        <td style="padding: 0 15px 0 0; color: #637381">
          {{ $unit->name }}
        </td>
        <td style="padding: 0 15px 0 0; color: #637381">
          {{ $amount }}
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
