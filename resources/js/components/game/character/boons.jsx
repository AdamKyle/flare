import React from 'react';
import ReactDatatable from '@ashvin27/react-datatable';
import moment from 'moment';
import {CountdownCircleTimer} from 'react-countdown-circle-timer';
import {Alert} from 'react-bootstrap';
import Card from '../components/templates/card';
import BoonModal from "./modals/boon-modal";

export default class Boons extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      characterBoons: [],
      boonToCancel: null,
      showBoonModal: false,
      loading: true,
      showSuccess: false,
    }

    this.boons_config = {
      page_size: 5,
      length_menu: [5, 10, 15],
      show_pagination: true,
      pagination: 'advance',
      hideSizePerPage: true,
    }

    this.boon_columns = [
      {
        name: "boon-name",
        text: "Type",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.type}</div>
        </div>,
      },
      {
        name: "affects-skills",
        text: "Affects Skills",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.affected_skills.length > 0 ? row.affected_skills : 'None'}</div>
        </div>,
      },
      {
        name: "completed-at",
        text: "Completed in",
        cell: row => <div data-tag="allowRowEvents">
          <div>{this.fetchTime(row.complete)}</div>
        </div>,
      },
    ];

    this.updateUnitMovements = Echo.private('update-boons-' + this.props.userId);
  }

  componentDidMount() {
    this.fetchBoons();

    this.updateUnitMovements.listen('Game.Core.Events.CharacterBoonsUpdateBroadcastEvent', (event) => {
      this.setState({
        characterBoons: event.boons,
      });
    });
  }

  fetchBoons() {
    axios.get('/api/character-sheet/' + this.props.characterId + '/active-boons').then((result) => {
      this.setState({
        characterBoons: result.data.active_boons,
        loading: false,
      });
    }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          return location.reload()
        }

        if (response.status === 429) {
          return window.location.replace('/game');
        }
      }
    });
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

  cancelBoon(event, data, rowIndex) {
    this.setState({
      boonToCancel: data,
      showBoonModal: true,
    })
  }

  closeCancelBoon() {
    this.setState({
      boonToCancel: null,
      showBoonModal: false,
    })
  }

  showSuccess(show) {
    this.setState({
      showSuccess: show
    });
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
              {
                this.state.showSuccess ?
                  <Alert variant="success" onClose={() => this.showSuccess(false)} dismissible>
                      You removed a boon!
                  </Alert>
                :
                  null
              }
              <ReactDatatable
                config={this.boons_config}
                records={this.state.characterBoons}
                columns={this.boon_columns}
                onRowClicked={this.cancelBoon.bind(this)}
              />
            </>
        }
        {
          this.state.showBoonModal ?
            <BoonModal
              show={this.state.showBoonModal}
              close={this.closeCancelBoon.bind(this)}
              boon={this.state.boonToCancel}
              fetchBoons={this.fetchBoons.bind(this)}
              characterBoons={this.state.characterBoons}
              showSuccess={this.showSuccess.bind(this)}
            />
            : null
        }
      </Card>
    )
  }
}
