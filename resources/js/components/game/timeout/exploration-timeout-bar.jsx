import React from 'react';
import moment, {max} from 'moment';
import {CountdownCircleTimer} from "react-countdown-circle-timer";

const renderTime = value => {
  if (value === 0) {
    return <div className="timer">Ready</div>;
  }

  return (
    <div className="timer">
      <div className="value">{value}</div>
    </div>
  );
};

export default class ExplorationTimeOutBar extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      maxTimeOut: 0,
      active: false,
    }

    this.echo = Echo.private(this.props.channel);
  }

  componentDidMount() {
    let maxTimeOut = 0;

    if (this.props.timeRemaining !== null) {
      let now = moment();
      let then = moment(this.props.timeRemaining);

      let duration = moment.duration(then.diff(now));

      maxTimeOut = duration.asSeconds();
    }

    this.setState({
      maxTimeOut: maxTimeOut,
      active: maxTimeOut > 0,
    });

    this.echo.listen(this.props.eventClass, (event) => {

      let forLength = event.hasOwnProperty('timeout') ? event.timeout : 10;

      if (event.hasOwnProperty('forLength')) {
        if (event.forLength !== 0) {

          if (event.hasOwnProperty('setSail')) {
            if (event.setSail) {
              forLength = event.forLength * 60;
            } else {
              forLength = event.forLength;
            }
          } else if (event.hasOwnProperty('canAdventureAgainAt')) {
            forLength = event.canAdventureAgainAt;
          } else {
            forLength = event.forLength;
          }
        }
      }

      this.setState({
        maxTimeOut: event.activateBar ? forLength : 0,
        active: event.activateBar,
      }, () => {
        if (this.props.hasOwnProperty('updateCanAttack')) {
          this.props.updateCanAttack(event.canAttack);
        }
      });
    });
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    if (this.props.timeRemaining !== null && (this.state.maxTimeOut === null || this.state.maxTimeOut <= 0)) {
      let now  = moment();
      let then = moment().add(this.props.timeRemaining, 'seconds');

      let duration = moment.duration(then.diff(now));

      const maxTimeOut = duration.asSeconds();

      this.setState({
        maxTimeOut: maxTimeOut,
        active: maxTimeOut > 0,
      });
    }
  }

  componentWillUnmount() {
    Echo.leaveChannel(this.props.chanel);
  }

  fetchTimer() {
    const maxTimeOut = this.state.maxTimeOut;
    const isHours = (maxTimeOut / 3600) > 1;
    const isMinutes = (maxTimeOut / 60) > 1;

    if (maxTimeOut === 0) {
      return;
    }

    if (isHours) {
      return (
        <div className={this.props.cssClass}>
          <div className={this.props.innerTimerCss}>
            <CountdownCircleTimer
              isPlaying={this.state.active}
              duration={maxTimeOut}
              initialRemainingTime={maxTimeOut}
              colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
              size={40}
              strokeWidth={2}
              onComplete={() => [false, 0]}
            >
              {({remainingTime}) => (remainingTime / 3600).toFixed(0)}
            </CountdownCircleTimer>
          </div>
          <div>Hours</div>
        </div>
      );
    } else if (isMinutes) {
      return (
        <div className={this.props.cssClass}>
          <div className={this.props.innerTimerCss}>
            <CountdownCircleTimer
              isPlaying={this.state.active}
              duration={maxTimeOut}
              initialRemainingTime={maxTimeOut}
              colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
              size={40}
              strokeWidth={2}
              onComplete={() => [false, 0]}
            >
              {({remainingTime}) => (remainingTime / 60).toFixed(0)}
            </CountdownCircleTimer>
          </div>
          <div>Minutes</div>
        </div>
      );
    } else {
      return (
        <div className={this.props.cssClass}>
          <div className={this.props.innerTimerCss}>
            <CountdownCircleTimer
              isPlaying={this.state.active}
              duration={maxTimeOut}
              initialRemainingTime={maxTimeOut}
              colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
              size={40}
              strokeWidth={2}
              onComplete={() => [false, 0]}
            >
              {({remainingTime}) => remainingTime}
            </CountdownCircleTimer>
          </div>
          <div>Seconds</div>
        </div>
      );
    }
  }

  render() {
    if (this.state.maxTimeOut <= 0) {
      return (
        <div className={this.props.readyCssClass}>
          Ready!
        </div>
      )
    }

    return (
      <>{this.fetchTimer()}</>
    );
  }
}
