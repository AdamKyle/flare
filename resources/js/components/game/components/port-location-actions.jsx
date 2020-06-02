import React   from 'react';
import SetSail from '../map/components/set-sail';

export default class PortLocationActions extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      portDetails: {
        currentPort: null,
        portLocations: [],
        characterId: null,
        isDead: false,
        canMove: true,
      },
      isLoading: true,
    }
  }

  componentDidMount() {
    this.setState({
      portDetails: this.props.portDetails,
      isLoading:   false,
    });
  }

  componentDidUpdate(prevProps) {
    if (this.props !== prevProps) {
      this.setState({
        portDetails: this.props.portDetails,
      });
    }
  }

  updatePlayerPosition(position) {
    this.props.updatePlayerPosition(position);
  }

  hidePort() {
    this.props.openPortDetails(false);
  }

  render() {
    if (this.state.isLoading) {
      return <>Please wait ...</>
    }

    return (
      <div className="card">
				<div className="card-body p-3">
          <div className="clearfix">
            <h4 className="card-title float-left">Set Sail</h4>
            <button className="float-right btn btn-sm btn-danger" onClick={this.hidePort.bind(this)}>Close</button>
          </div>
          <hr />
					<div className="row">
            <SetSail 
              characterIsDead={this.state.portDetails.characterIsDead}
              currentPort={this.state.portDetails.currentPort} 
              portList={this.state.portDetails.portList} 
              characterId={this.state.portDetails.characterId} 
              updatePlayerPosition={this.updatePlayerPosition.bind(this)}
              canMove={this.state.portDetails.canMove}
              userId={this.props.userId}
              updatePlayerPosition={this.props.updatePlayerPosition}
            />
          </div>
        </div>
      </div>
    )
  }
} 