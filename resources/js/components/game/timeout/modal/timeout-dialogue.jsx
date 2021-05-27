import React from 'react';
import {Modal, ModalDialog} from 'react-bootstrap';
import moment from "moment";
import {CountdownCircleTimer} from "react-countdown-circle-timer";

export default class TimeoutDialogue extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      timeOutFor: this.props.timeOutFor,
    }

    this.timeout = Echo.private('global-timeout-' + this.props.userId);
  }

  componentDidMount() {

    if (this.props.timeOutFor === 0) {
      axios.post('/api/character-timeout').then((response) => {
        this.setState({
          timeOutFor: response.data.timeout_until,
        });
      }).catch((err) => {
        console.error(err);
      });
    }

    this.timeout.listen('Game.Core.Events.GlobalTimeOut', (event) => {
      console.log(event);
      if (event.user.timeout_until === null) {
        location.reload();
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
        <div style={{marginLeft: 'auto', marginRight: 'auto', marginTop: 0, marginBottom: 0}}>
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
        </div>
      );
    } else {
      return null;
    }
  }

  render() {
    return (
      <Modal
        show={this.props.show}
        centered
      >
        <Modal.Header closeButton>
          <Modal.Title>
            Too many actions
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p>
            You have been a very busy bee. Time to slow down a bit. The page will refresh when this timer is done.
          </p>
          {this.fetchTime(this.state.timeOutFor)}
        </Modal.Body>
      </Modal>
    )
  }
}
