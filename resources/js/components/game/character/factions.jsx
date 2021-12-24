import React from 'react';
import ReactDatatable from '@ashvin27/react-datatable';
import Card from '../components/templates/card';
import AlertInfo from "../components/base/alert-info";

export default class Factions extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      characterBoons: [],
      boonToCancel: null,
      showBoonModal: false,
      showSuccess: false,
    }

    this.factionsConfig = {
      page_size: 5,
      length_menu: [5, 10, 15],
      show_pagination: true,
      pagination: 'advance',
      hideSizePerPage: true,
    }

    this.factionColumns = [
      {
        name: "map_name",
        text: "Map",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.map_name}</div>
        </div>,
      },
      {
        name: "title",
        text: "Title",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.title === null ? 'N/A' : row.title}</div>
        </div>,
      },
      {
        name: "current_level",
        text: "Level",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.current_level}</div>
        </div>,
      },
      {
        name: "current_points",
        text: "Current Pts.",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.current_points}</div>
        </div>,
      },
      {
        name: "points_needed",
        text: "Points Needed",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.points_needed}</div>
        </div>,
      },
    ];
  }

  render() {
    return (
      <Card>
        <AlertInfo icon={'fas fa-question-circle'} title={"Quick Tip"}>
          <p>
            Factions are best farmed by killing <strong>ANY</strong> monster on the
            game map, as shown in the table.
          </p>
          <p>
            Killing monsters will get you 1 point for the first level of each faction
            and 2 points there after for each kill. For example you need 500 Kills on surface to advance
            to the next level. For level 2 you need a 500 kills but need 1000 points (2 points) to get to level 3.
            See <a href={"/information/factions"}>Factions</a> in the help docs for more info.
          </p>
          <p>Oh ya .... Use auto battle for this. Do not try and grind this out manually.</p>
        </AlertInfo>
        <ReactDatatable
          config={this.factionsConfig}
          records={this.props.factions}
          columns={this.factionColumns}
        />
      </Card>
    )
  }
}