import React from 'react';
import moment, { max } from 'moment';
import { CountdownCircleTimer } from "react-countdown-circle-timer";

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

export default class TimeOutBar extends React.Component {

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
      let now    = moment();
      let then   = moment(this.props.timeRemaining);

      let duration = moment.duration(then.diff(now));

      maxTimeOut = duration.asSeconds();
    }

    this.setState({
      maxTimeOut: maxTimeOut,
      active: maxTimeOut > 0,
    });

    this.echo.listen(this.props.eventClass, (event) => {
      let forLength = 10;

      if (event.hasOwnProperty('forLength')) {
        if (event.forLength !== 0) {
          forLength = event.forLength;
        }
      }
      
      this.setState({
        maxTimeOut: event.activatebar ? forLength : 0,
        active: event.activatebar,
      });
    });
  }

  componentWillUnmount() {
    Echo.leaveChannel(this.props.chanel);
  }

  fetchTimer() {
    const maxTimeOut = this.state.maxTimeOut;
    const isHours    = (maxTimeOut / 3600) > 1;
    const isMinutes  = (maxTimeOut / 60) > 1;

    if (isHours) {
      return (
        <div className={this.props.cssClass}>
          <div className="float-left">
            <CountdownCircleTimer
              isPlaying={this.state.active}
              duration={maxTimeOut}
              initialRemainingTime={maxTimeOut}
              colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
              size={40}
              strokeWidth={2}
              onComplete={() => [false, 0]}
            >
              {({ remainingTime }) => (remainingTime / 3600).toFixed(0)}
            </CountdownCircleTimer>
          </div>
          <div className="float-left mt-2 ml-2">Hours</div>
        </div>
      );
    } else if (isMinutes) {
      return (
        <div className={this.props.cssClass}>
          <div className="float-left">
            <CountdownCircleTimer
              isPlaying={this.state.active}
              duration={maxTimeOut}
              initialRemainingTime={maxTimeOut}
              colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
              size={40}
              strokeWidth={2}
              onComplete={() => [false, 0]}
            >
              {({ remainingTime }) => (remainingTime / 60).toFixed(0)}
            </CountdownCircleTimer>
          </div>
          <div className="float-left mt-2 ml-2">Minutes</div>
        </div>
      );
    } else {
      return (
        <div className={this.props.cssClass}>
          <div className="float-left">
            <CountdownCircleTimer
              isPlaying={this.state.active}
              duration={maxTimeOut}
              initialRemainingTime={maxTimeOut}
              colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
              size={40}
              strokeWidth={2}
              onComplete={() => [false, 0]}
            >
              {({ remainingTime }) => remainingTime}
            </CountdownCircleTimer>
          </div>
          <div className="float-left mt-2 ml-2">Seconds</div>
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

    return(
      <>{this.fetchTimer()}</>
    );
  }
}
