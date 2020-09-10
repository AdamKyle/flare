import React from 'react';
import { getServerMessage } from '../../helpers/server_message';
import Collapse from 'react-bootstrap/Collapse'

export default class AdventureMenu extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      logs: [],
      open: false,
    }

    this.adventureLogs = Echo.private('update-adventure-logs-' + this.props.userId);

  }

  componentDidMount() {

    axios.get('/api/character/adventure/logs').then((result) => {
      this.setState({
        logs: result.data,
      });
    }).catch(function (error) {
      return getServerMessage('something_went_wrong');
    });

    this.adventureLogs.listen('Game.Maps.Adventure.Events.UpdateAdventureLogsBroadcastEvent', (event) => {
      this.setState({
        logs: event.adventureLogs,
      });
    });
  }

  open() {
    this.setState({
      open: this.state.open ? false : true
    });
  }

  hasRewardsToCollect() {
    if (!_.isEmpty(this.state.logs)) {
      let hasUnCollectedReward = false;

      this.state.logs.forEach((log) => {
        if (log.rewards !== null) {
          hasUnCollectedReward = true;
          return;
        }
      });
      
      return hasUnCollectedReward;
    }

    return false;
  }

  renderCurrentAdventureLink() {
    if (this.hasRewardsToCollect()) {
        return <><a href="/current-adventure/" >Current Adventure <i className="ml-3 text-success fas fa-check-double fa-bounce"></i></a></>
    }

    return null;
  }

  render() {


    return (
      <>
        <a className="has-arrow" 
          href="#" onClick={this.open.bind(this)} 
          aria-expanded={open} aria-controls="adventure-links">
            <i className="ra ra-trail"></i>
            <span className="hide-menu">
              Adventure Logs {this.hasRewardsToCollect() ? <i className="ml-3 text-success fas fa-check-double fa-bounce"></i> : null}
            </span>
          </a>
        <Collapse in={this.state.open}>
          <ul id="adventure-links">
            <li>{this.renderCurrentAdventureLink()}</li>
            <li><a href="/current-adventures/">Completed Adventures</a></li>
          </ul>
        </Collapse>
      </>
    );
  }
}