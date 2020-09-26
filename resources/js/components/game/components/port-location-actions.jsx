import React   from 'react';
import SetSail from '../map/components/set-sail';
import CardTemplate from './templates/card-template';
import ContentLoader, { Facebook } from 'react-content-loader';

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
      return (
        <CardTemplate>
          <ContentLoader viewBox="0 0 380 30">
            {/* Only SVG shapes */}    
            <rect x="0" y="0" rx="4" ry="4" width="250" height="5" />
            <rect x="0" y="8" rx="3" ry="3" width="250" height="5" />
            <rect x="0" y="16" rx="4" ry="4" width="250" height="5" />
          </ContentLoader>
        </CardTemplate>
      );
    }

    return (
      <CardTemplate
        OtherCss="p-3"
        cardTitle="Set Sail"
        close={this.hidePort.bind(this)}
      >
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
            updateAdventure={this.props.updateAdventure}
          />
        </div>
      </CardTemplate>
    )
  }
} 