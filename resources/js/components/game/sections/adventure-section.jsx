import React from 'react';
import {Row, Col} from 'react-bootstrap';
import AdventureEmbark from './modals/adventure-embark';
import TimeOutBar from '../timeout/timeout-bar';
import Card from '../components/templates/card';
import ContentLoader, {Facebook} from 'react-content-loader';

export default class AdeventureActions extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      adventureDetails: [],
      isLoading: true,
      showEmbark: false,
      adventure: null,
      message: null,
      failed: false,
      tookToLong: false,
      canceled: false,
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

    this.adventureLogs.listen('Game.Adventures.Events.UpdateAdventureLogsBroadcastEvent', (event) => {
      const lastLog = event.adventureLogs[event.adventureLogs.length - 1];
      let failed    = false;
      let canceled  = false;
      let tooLong   = false;

      if (typeof lastLog !== 'undefined') {
        if (!lastLog.in_progress && !event.canceled) {
          failed = !lastLog.complete && !lastLog.took_to_long ? true : false;
        }

        if (event.canceled) {
          canceled = true;
        }

        tooLong = lastLog.took_to_long;
      }

      this.setState({
        characterAdventureLogs: event.adventureLogs,
        canAdventureAgainAt: event.canAdventureAgainAt,
        message: null,
        failed: failed,
        canceled: canceled,
        tookToLong: tooLong
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

    axios.post('/api/character/' + this.props.characterId + '/adventure/' + adventure.id + '/cancel').then((result) => {
      this.setState({
        message: result.data.message,
        canAdventureAgainAt: null,
      });
    }).catch((error) => {
      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          location.reload();
        }

        if (response.status === 429) {
          location.reload();
        }
      }
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
        cssClass={'float-left adventure-timeout-bar'}
        readyCssClass={'character-ready'}
        forSeconds={this.state.canAdventureAgainAt}
        timeRemaining={this.state.canAdventureAgainAt}
        channel={'show-timeout-bar-' + this.props.userId}
        eventClass={'Game.Adventures.Events.UpdateAdventureLogsBroadcastEvent'}
      />
    )
  }

  adventures() {
    const details = [];

    let foundAdventure = null;

    const hasAdventureInProgress = !_.isEmpty(this.state.characterAdventureLogs.filter(al => al.in_progress === true));

    const hasCollectedRewards = !_.isEmpty(this.state.characterAdventureLogs.filter(al => al.rewards !== null));

    _.forEach(this.state.adventureDetails, (adventure) => {

      if (!_.isEmpty(this.state.characterAdventureLogs)) {
        const matching = this.state.characterAdventureLogs.filter(al => al.adventure_id === adventure.id && al.in_progress === true);

        if (matching.length > 0) {
          foundAdventure = matching[0];
        }
      }

      details.push(
        <div key={adventure.id} className="mb-2">
          <Row>
            <Col xs={3} sm={3} lg={3} xl={2}>
              <a href={'/adventures/' + adventure.id} target="_blank"> {adventure.name} </a>
            </Col>
            <Col xs={9} sm={9} lg={9} xl={9}>
              <Row>
                <Col xs={9} sm={6} lg={8} xl={3}>
                  <button className="mr-2 btn btn-sm btn-primary" data-adventure-id={adventure.id}
                          disabled={hasAdventureInProgress || hasCollectedRewards || !this.props.canAdventure()}
                          onClick={this.embarkShow.bind(this)}>Embark
                  </button>
                  {
                    foundAdventure !== null ?
                      foundAdventure.adventure_id === adventure.id ?
                        <button className="mr-2 btn btn-sm btn-danger" data-adventure-id={adventure.id}
                                onClick={this.cancelAdventure.bind(this)}>Cancel</button>
                        : null
                      : null
                  }
                </Col>
                <Col xs={3} sm={6} lg={4} xl={9}>
                  {
                    foundAdventure !== null ?
                      foundAdventure.adventure_id === adventure.id ?
                        this.timeOutBar()
                        : null
                      : null
                  }
                </Col>
              </Row>
            </Col>
          </Row>
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
        <Card>
          <ContentLoader viewBox="0 0 380 30">
            {/* Only SVG shapes */}
            <rect x="0" y="0" rx="4" ry="4" width="250" height="5"/>
            <rect x="0" y="8" rx="3" ry="3" width="250" height="5"/>
            <rect x="0" y="16" rx="4" ry="4" width="250" height="5"/>
          </ContentLoader>
        </Card>
      );
    }

    const hasAdventureInProgress = !_.isEmpty(this.state.characterAdventureLogs.filter(al => al.in_progress === true));

    const hasCollectedRewards = !_.isEmpty(this.state.characterAdventureLogs.filter(al => al.rewards !== null));

    return (
      <Card
        cardTitle="Adventures"
        close={this.hideAdventure.bind(this)}
        otherClasses="p-3"
      >
        {this.state.tookToLong ?
          <div className="alert alert-info">Your adventure took too long, you decided to flee. You gained no items or
            loot. You can review the logs <a href="/current-adventure/">here</a>.</div> : null}
        {this.state.failed ?
          <div className="alert alert-danger">You have died. Maybe checking the logs might help you. You can do so <a
            href="/current-adventure/">here</a>.</div> : null}
        {this.state.canceled ?
          <div className="alert alert-success">Adventure canceled. You gained no rewards.</div> : null}
        {hasCollectedRewards && !hasAdventureInProgress ?
          <div className="alert alert-info">Cannot start adventure till you collect the rewards from the previous
            adventure. You can do so <a href="/current-adventure/">here</a>.</div> : null}
        {hasAdventureInProgress ?
          <div className="alert alert-info">You may only embark on one adventure at a time</div> : null}

        {this.adventures()}

        {this.state.showEmbark ? <AdventureEmbark
          characterId={this.props.characterId}
          adventure={this.state.adventure}
          show={this.state.showEmbark}
          embarkClose={this.embarkClose.bind(this)}
          updateMessage={this.updateMessage.bind(this)}
          updateCharacterAdventures={this.updateCharacterAdventures.bind(this)}
        /> : null}
      </Card>
    )
  }
}
