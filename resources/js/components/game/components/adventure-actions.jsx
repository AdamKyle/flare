import React from 'react';
import AdventureEmbark from './modals/adventure-embark';

export default class AdeventureActions extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      adventureDetails: [],
      isLoading: true,
      showEmbark: false,
      adventure: null,
      message: null,
      characterAdventureLogs: [],
    }

    this.adventureLogs = Echo.private('update-adventure-logs-' + this.props.userId);
  }

  componentDidMount() {
    this.setState({
      adventureDetails: this.props.adventureDetails,
      characterAdventureLogs: this.props.adventureLogs,
      isLoading: false,
    });

    this.adventureLogs.listen('Game.Core.Events.UpdateAdventureLogsBroadcastEvent', (event) => {
      this.setState({
        characterAdventureLogs: event.adventureLogs,
      });
    });
  }

  componentDidUpdate(prevProps) {
    if (this.props !== prevProps) {
      this.setState({
        adventureDetails: this.props.adventureDetails,
      });
    }
  }

  hideAdventure() {
    this.props.openAdventureDetails(false);
  }

  embarkShow(event) {
    const adventure = this.state.adventureDetails.filter(a => a.id === parseInt(event.target.getAttribute('data-adventure-id')))[0];

    this.setState({
      showEmbark: true,
      adventure: adventure,
    });
  }

  cancelAdventure(event) {
    const adventure = this.state.adventureDetails.filter(a => a.id === parseInt(event.target.getAttribute('data-adventure-id')))[0];

    axios.post('/api/character/'+this.props.characterId+'/adventure/'+adventure.id+'/cancel').then((result) => {
      this.setState({
        message: result.data.message,
        characterAdventureLogs: result.data.adventure_logs
      });
    }).catch((error) => {
      console.error(error);
    });
  }

  embarkClose() {
    this.setState({
      showEmbark: false,
      adventure: null,
    });
  }

  updateMessage(message) {
    this.setState({
      message: message,
    });
  }

  updateCharacterAdventures(adventureLogs) {
    this.setState({
      characterAdventureLogs: adventureLogs,
    });
  }

  adventures() {
    const details = [];

    let foundAdventure = null;

    _.forEach(this.state.adventureDetails, (adventure) => {

        if (!_.isEmpty(this.state.characterAdventureLogs)) {
          const matching = this.state.characterAdventureLogs.filter(al => al.adventure_id === adventure.id && al.in_progress === true);

          if (matching.length > 0) {
            foundAdventure = matching[0];
          }
        }

        details.push(
            <div className="row mb-2" key={adventure.id}>
                <div className="col-md-2">{adventure.name}</div>
                <div className="col-md-10">
                    <button className="mr-2 btn btn-primary" data-adventure-id={adventure.id} onClick={this.embarkShow.bind(this)}>Embark</button>
                    <a href={'/adeventures/' + adventure.id} target="_blank" className="mr-2 btn btn-primary">Details</a>
                    { foundAdventure !== null ? foundAdventure.adventure_id === adventure.id ? <button className="mr-2 btn btn-danger" data-adventure-id={adventure.id} onClick={this.cancelAdventure.bind(this)}>Cancel Adventure</button> : null : null }
                </div>
            </div>
        );
    });

    return details;
  }

  render() {
    if (this.state.isLoading) {
      return <>Please wait ...</>
    }

    return (
      <div className="card">
        <div className="card-body p-3">
          <div className="clearfix">
            <h4 className="card-title float-left">Adventures</h4>
            <button className="float-right btn btn-sm btn-danger" onClick={this.hideAdventure.bind(this)}>Close</button>
          </div>
          <hr />
          {this.state.message !== null ? <div className="alert alert-success">
            {this.state.message}
          </div> : null}
          <div className="alert alert-info">You may only embark on one adventure at a time</div>
          {this.adventures()}
        </div>

        {this.state.showEmbark ? <AdventureEmbark 
          characterId={this.props.characterId} 
          adventure={this.state.adventure} 
          show={this.state.showEmbark} 
          embarkClose={this.embarkClose.bind(this)} 
          updateMessage={this.updateMessage.bind(this)}
          updateCharacterAdventures={this.updateCharacterAdventures.bind(this)}
        /> : null }
      </div>
    )
  }
} 