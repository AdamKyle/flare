import React from 'react';
import {getServerMessage} from "../../../helpers/server_message";
import Clock from "./clock";

export default class ServerTimeClock extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      severTime: null,
    }
  }

  componentDidMount() {

    axios.get('/api/server-time').then((response) => {
      this.setState({
        serverTime: response.data.server_time,
      });
    }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          return location.reload()
        }

        if (response.status === 429) {
          this.props.openTimeOutModal()
        }
      }

      return getServerMessage('something_went_wrong');
    })
  }

  render() {
    return (
      <Clock serverTime={this.state.serverTime} />
    );
  }
}