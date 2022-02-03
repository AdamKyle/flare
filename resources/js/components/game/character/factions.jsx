import React from 'react';
import ReactDatatable from '@ashvin27/react-datatable';
import Card from '../components/templates/card';
import AlertInfo from "../components/base/alert-info";

export default class Factions extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      factions: [],
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

  componentDidMount() {
    axios.get('/api/character-sheet/'+this.props.characterId+'/factions').then((result) => {
      this.setState({
        factions: result.data.factions,
        loading: false
      })
    }).catch((err) => {
      this.setState({loading: false});

      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload()
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal()
        }
      }
    });
  }

  render() {

    if (this.state.loading) {
      return (
        <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
          <div className="progress-bar progress-bar-striped indeterminate">
          </div>
        </div>
      );
    }

    return (
      <Card>
        { this.props.canAutoBattle ?
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
        :
          <AlertInfo icon={'fas fa-question-circle'} title={"Automation is disabled"}>
            <p>
              Because The Creator disabled automation due to server issues. The Faction system has been altered to allow you to
              still gain faction points. The cost per level is now 10x less.
            </p>
            <p>
              When automation returns or if The Creator enables it for your account, your Factions points will adjust accordingly to
              required 10x more.
            </p>
            <p>
              The idea with these is that you kill any monster on the map specified to gain points to level the faction which gets you
              titles and at every level a special unique green item that you can equip. From level 0 to level 1, you only get one point.
              After level 1 you get 2 points per kill. The points needed will double every level to a total of 800 points to cap level 4.
            </p>
            <p>
              Specific Faction levels are required for some quests.
            </p>
          </AlertInfo>
        }

        <ReactDatatable
          config={this.factionsConfig}
          records={this.state.factions}
          columns={this.factionColumns}
        />
      </Card>
    )
  }
}
