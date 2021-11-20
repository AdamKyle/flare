import React from 'react';
import {getServerMessage} from '../../helpers/server_message';
import Collapse from 'react-bootstrap/Collapse'

export default class KingdomsMenu extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      logs: [],
      characterId: 0,
      open: false,
    }

    this.kingdomLogs = Echo.private('update-kingdom-logs-' + this.props.userId);

  }

  componentDidMount() {

    axios.get('/api/kingdoms/'+this.props.userId+'/attack-logs').then((result) => {
      this.setState({
        logs: result.data.logs,
        characterId: result.data.character_id,
      });
    }).catch(function (error) {
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
    });

    this.kingdomLogs.listen('Game.Kingdoms.Events.UpdateKingdomLogs', (event) => {
      console.log(event);
      this.setState({
        logs: event.logs,
      });
    });
  }

  open() {
    this.setState({
      open: this.state.opened ? false : true
    });
  }

  hasUnOpenedLogs() {
    if (!_.isEmpty(this.state.logs)) {
      return this.state.logs.filter((l) => !l.opened).length > 0;
    }

    return false;
  }

  render() {

    return (
      <>
        <a className="has-arrow"
           href="#" onClick={this.open.bind(this)}
           aria-expanded={open} aria-controls="kingdom-links">
          <i className={'ra ra-guarded-tower ' + (this.hasUnOpenedLogs() ? 'text-warning fa-bounce' : null)}></i>
          <span className="hide-menu">
              Kingdoms
          </span>
        </a>
        <Collapse in={this.state.open}>
          <ul id="adventure-links">
            <li><a href={'/kingdom/'+this.state.characterId+'/attack-logs'} className={this.hasUnOpenedLogs() ? 'text-warning' : ''}>
              {this.hasUnOpenedLogs() ? <span><i className="fas fa-exclamation mr-2"></i> Attack Logs</span> : 'Attack Logs'}
            </a></li>
            <li><a href={'/kingdom/'+this.state.characterId+'/unit-movement'}>Unit Movement</a></li>
          </ul>
        </Collapse>
      </>
    );
  }
}
