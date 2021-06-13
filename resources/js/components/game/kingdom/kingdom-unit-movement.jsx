import React from 'react';
import ReactDatatable from '@ashvin27/react-datatable';
import moment from 'moment';
import {CountdownCircleTimer} from 'react-countdown-circle-timer';
import Card from '../components/templates/card';
import KingdomUnitRecallUnit from './modal/kingdom-unit-recall';

export default class KingdomUnitMovement extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      unitsInMovement: [],
      unitsToRecall: null,
      showUnitRecallModal: false,
      loading: true,
    }

    this.unit_movement_queue_config = {
      page_size: 5,
      length_menu: [5, 10, 15],
      show_pagination: true,
      pagination: 'advance',
      hideSizePerPage: true,
    }

    this.unit_movement_column = [
      {
        name: "from-kingdom-name",
        text: "From Kingdom Name",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.from_kingdom_name}</div>
        </div>,
      },
      {
        name: "to-kingdom-name",
        text: "To Kingdom Name",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.to_kingdom_name}</div>
        </div>,
      },
      {
        name: 'moving-status',
        text: 'Movement Type',
        cell: row => <div data-tag="allowRowEvents">
          <div>{this.fetchType(row)}</div>
        </div>,
      },
      {
        key: "total-amount",
        text: "Total Amount",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.total_amount}</div>
        </div>,
      },
      {
        name: "completed-at",
        text: "Completed in",
        cell: row => <div data-tag="allowRowEvents">
          <div>{this.fetchTime(row.completed_at)}</div>
        </div>,
      },
    ];

    this.updateUnitMovements = Echo.private('update-units-in-movement-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/kingdom-unit-movement/' + this.props.characterId).then((result) => {
      this.setState({
        unitsInMovement: result.data,
        loading: false,
      });
    }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401 || response.status === 429) {
          return location.reload()
        }
      }
    });

    this.updateUnitMovements.listen('Game.Kingdoms.Events.UpdateUnitMovementLogs', (event) => {
      this.setState({
        unitsInMovement: event.unitMovement,
      });
    });
  }

  fetchType(item) {
    if (item.is_attacking) {
      return 'Attacking';
    }

    if (item.is_returning) {
      return 'Returning';
    }

    if (item.is_recalled) {
      return 'Recalled';
    }

    if (item.is_moving) {
      return 'Moving';
    }
  }

  fetchTime(time) {
    let now = moment();
    let then = moment(time);

    let duration = moment.duration(then.diff(now)).asSeconds();

    const isHours = (duration / 3600) >= 1;

    if (duration > 0) {
      return (
        <>
          <div className="float-left">
            {isHours ?
              <CountdownCircleTimer
                isPlaying={true}
                duration={duration}
                initialRemainingTime={duration}
                colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
                size={40}
                strokeWidth={2}
                onComplete={() => [false, 0]}
              >
                {({remainingTime}) => (remainingTime / 3600).toFixed(0)}
              </CountdownCircleTimer>
              :
              <CountdownCircleTimer
                isPlaying={true}
                duration={duration}
                initialRemainingTime={duration}
                colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
                size={40}
                strokeWidth={2}
                onComplete={() => [false, 0]}
              >
                {({remainingTime}) => (remainingTime / 60).toFixed(0)}
              </CountdownCircleTimer>
            }
          </div>
          <div className="float-left mt-2 ml-3">{isHours ? 'Hours' : 'Minutes'}</div>
        </>

      );
    } else {
      return null;
    }
  }

  returnUnits(event, data, rowIndex) {
    this.setState({
      unitsToRecall: data,
      showUnitRecallModal: true,
    });
  }

  closeUnitRecall() {
    this.setState({
      unitsToRecall: null,
      showUnitRecallModal: false,
    })
  }

  render() {
    if (this.state.loading) {
      return (
        <Card>
          <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
            <div className="progress-bar progress-bar-striped indeterminate">
            </div>
          </div>
        </Card>
      )
    }

    return (
      <Card>
        {
          this.state.loading ?
            <div className="progress loading-progress" style={{position: 'relative'}}>
              <div className="progress-bar progress-bar-striped indeterminate">
              </div>
            </div>

            :

            <>
              <ReactDatatable
                config={this.unit_movement_queue_config}
                records={this.state.unitsInMovement}
                columns={this.unit_movement_column}
                onRowClicked={this.returnUnits.bind(this)}
              />
              {
                this.state.unitsToRecall !== null ?
                  <KingdomUnitRecallUnit
                    show={this.state.showUnitRecallModal}
                    close={this.closeUnitRecall.bind(this)}
                    unitsToRecall={this.state.unitsToRecall}
                    unitsInMovement={this.state.unitsInMovement}
                    characterId={this.props.characterId}
                  /> : null
              }
            </>
        }
      </Card>
    )
  }
}
