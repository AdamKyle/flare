import React from 'react';
import moment from 'moment';

export default class Clock extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      time: null,
    }
  }

  componentDidMount() {

    setInterval(()=>{
      this.setState({
        time: moment(this.props.serverTime).format('LTS')
      });
    },1000)
  }

  render() {
    return (
      <span>Server Time: {this.state.time} (GMT-7)</span>
    )
  }
}