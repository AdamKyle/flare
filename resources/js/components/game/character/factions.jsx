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

    this.updateFactions = Echo.private('update-factions-' + this.props.userId);
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

    this.updateFactions.listen('Game.Core.Events.UpdateCharacterFactions', (event) => {
      this.setState({
        factions: event.factions,
      });
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
          <p>There is a quest you can do for the Helpless Goblin that will get you a quest item that gives you 10 points pr kill as opposed to 2, but only starting at level 1.</p>
          <p>Players will want to use Exploration to gain these points. I would suggest you do that quest first.</p>
        </AlertInfo>

        <ReactDatatable
          config={this.factionsConfig}
          records={this.state.factions}
          columns={this.factionColumns}
        />
      </Card>
    )
  }
}
