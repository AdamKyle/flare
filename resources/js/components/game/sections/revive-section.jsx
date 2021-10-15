import React from 'react';
import {getServerMessage} from "../helpers/server_message";

export default class ReviveSection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      isLoading: false,
    }
  }

  revive() {
    if (!this.props.canAttack) {
      return getServerMessage('cant_attack');
    }

    this.setState({isLoading: true});

    axios.post(this.props.route).then((result) => {
      this.props.revive(result.data, () => {
        this.setState({isLoading: false});
      });
    }).catch((err) => {
      this.setState({isLoading: false});

      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal();
        }
      }
    })
  }

  render() {
    return (
      <>
        <button className="btn btn-primary" onClick={this.revive.bind(this)}>
          Revive
          {this.state.isLoading ? <i className="fas fa-spinner fa-spin"></i> : null}
        </button>
        <p className="mt-3">You are dead. Click revive to live again.</p>
      </>
    );
  }
}
