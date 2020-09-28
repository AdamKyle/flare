import React from 'react';
import AdventureEmbark from './modals/adventure-embark';
import TimeOutBar from '../timeout/timeout-bar';
import CardTemplate from './templates/card-template';
import ContentLoader, { Facebook } from 'react-content-loader';

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
      canAdventureAgainAt: null,
    }

    this.adventureLogs = Echo.private('update-adventure-logs-' + this.props.userId);
  }

  componentDidMount() {
    this.setState({
      adventureDetails: this.props.adventureDetails,
      characterAdventureLogs: this.props.adventureLogs,
      canAdventureAgainAt: this.props.adventureAgainAt,
      isLoading: false,
    });

    this.adventureLogs.listen('Game.Maps.Adventure.Events.UpdateAdventureLogsBroadcastEvent', (event) => {
      this.setState({
        characterAdventureLogs: event.adventureLogs,
        canAdventureAgainAt: event.canAdventureAgainAt,
        message: null,
      }, () => {
        this.props.updateAdventure(this.state.adventureDetails, this.state.characterAdventureLogs, this.state.canAdventureAgainAt);
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
        canAdventureAgainAt: null,
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

  updateCharacterAdventures(adventureAgainAt) {
    this.setState({
      canAdventureAgainAt: adventureAgainAt,
    });
  }

  timeOutBar() {
    return (
      <TimeOutBar
        cssClass={'float-right adventure-timeout-bar'}
        readyCssClass={'character-ready'}
        forSeconds={this.state.canAdventureAgainAt}
        timeRemaining={this.state.canAdventureAgainAt}
        channel={'show-timeout-bar-' + this.props.userId}
        eventClass={'Game.Maps.Adventure.Events.UpdateAdventureLogsBroadcastEvent'}
      />
    )
  }

  adventures() {
    const details = [];

    let foundAdventure = null;
    
    const hasAdventureInProgres = !_.isEmpty(this.state.characterAdventureLogs.filter(al => al.in_progress === true));

    const hasCollectedRewards = !_.isEmpty(this.state.characterAdventureLogs.filter(al => al.rewards !== null));

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
                <div className="col-md-5">
                    <button className="mr-2 btn btn-sm btn-primary" data-adventure-id={adventure.id} disabled={hasAdventureInProgres || hasCollectedRewards || !this.props.canAdventure()} onClick={this.embarkShow.bind(this)}>Embark</button>
                    <a href={'/adeventures/' + adventure.id} className="mr-2">Details</a>
                    
                    { foundAdventure !== null ? foundAdventure.adventure_id === adventure.id ? <button className="mr-2 btn btn-sm btn-danger" data-adventure-id={adventure.id} onClick={this.cancelAdventure.bind(this)}>Cancel Adventure</button> : null : null }
                    { foundAdventure !== null ? foundAdventure.adventure_id === adventure.id ? this.timeOutBar() : null : null }
                </div>
            </div>
        );
    });

    return details;
  }

  removeMessage() {
    this.setState({
      message: null,
    });
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

    const hasAdventureInProgres = !_.isEmpty(this.state.characterAdventureLogs.filter(al => al.in_progress === true));

    const hasCollectedRewards = !_.isEmpty(this.state.characterAdventureLogs.filter(al => al.rewards !== null));

    return (
      <CardTemplate
        cardTitle="Adventures"
        close={this.hideAdventure.bind(this)}
        otherClasses="p-3"
      >
        { hasCollectedRewards ? <div className="alert alert-info">Cannot start adventure till you collect the rewards from the previous adventure. You can do so <a href="/current-adventure/">here</a>.</div> : null}
        { hasAdventureInProgres ? <div className="alert alert-info">You may only embark on one adventure at a time</div> : null }
        { !this.props.canAdventure() ? <div className="alert alert-info">You must wait to be able to move and attack in order to embark.</div> : null}
        {this.adventures()}

        {this.state.showEmbark ? <AdventureEmbark 
          characterId={this.props.characterId} 
          adventure={this.state.adventure} 
          show={this.state.showEmbark} 
          embarkClose={this.embarkClose.bind(this)} 
          updateMessage={this.updateMessage.bind(this)}
          updateCharacterAdventures={this.updateCharacterAdventures.bind(this)}
        /> : null }
      </CardTemplate>
    )
  }
} 