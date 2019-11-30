import React from 'react';
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

    this.echo = Echo.private(this.props.channel + this.props.userId);
  }

  componentDidMount() {
    this.echo.listen(this.props.eventName , (event) => {
      this.setState({
        maxTimeOut: event.activatebar ? 10 : 0,
        active: event.activatebar,
      });
    });
  }

  render() {
    if (this.state.maxTimeOut === 0) {
      return (
        <div className="character-ready">
          Ready!
        </div>
      )
    }
    return(
      <div className={this.props.cssClass}>
        <CountdownCircleTimer
          isPlaying={this.state.active}
          durationSeconds={this.state.maxTimeOut}
          colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
          renderTime={renderTime}
          size={40}
          strokeWidth={2}
          onComplete={() => [false, 0]}
        />
      </div>
    );
  }
}
