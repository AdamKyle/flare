import React from 'react';
import ReactDatatable from '@ashvin27/react-datatable';
import moment from 'moment';
import {CountdownCircleTimer} from 'react-countdown-circle-timer';
import {Alert} from 'react-bootstrap';
import Card from '../components/templates/card';
import BoonModal from "./modals/boon-modal";
import AlertInfo from "../components/base/alert-info";

export default class Automations extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      characterBoons: [],
      boonToCancel: null,
      showBoonModal: false,
      showSuccess: false,
    }

    this.automationsConfig = {
      page_size: 5,
      length_menu: [5, 10, 15],
      show_pagination: true,
      pagination: 'advance',
      hideSizePerPage: true,
    }

    this.automationColumns = [
      {
        name: "attack_type",
        text: "Type",
        cell: row => <div data-tag="allowRowEvents">
          <div>{row.attack_type.charAt(0).toUpperCase() + row.attack_type.slice(1)}</div>
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
  }

  componentDidMount() {
  }

  fetchTime(time) {
    let now  = moment();
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

  render() {
    return (
      <Card>
        <AlertInfo icon={"fas fa-question-circle"} title={"Attn!"}>
          <p>
            You cannot cancel your automation here.
            Instead head to the Game section and manually end them there.
          </p>
        </AlertInfo>
        <ReactDatatable
          config={this.automationsConfig}
          records={this.props.automations}
          columns={this.automationColumns}
        />
      </Card>
    )
  }
}
