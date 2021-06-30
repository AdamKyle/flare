import React from 'react';
import {Row, Col} from 'react-bootstrap';

export default class SetSail extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      portId: "",
      currentPort: this.props.currentPort,
      portList: this.props.portList,
      characterId: this.props.characterId,
      isDead: this.props.characterIsDead,
      canMove: this.props.canMove,
      adventures: [],
    };

    this.isDead = Echo.private('character-is-dead-' + this.props.userId);
  }

  componentDidMount() {
    this.isDead.listen('Game.Core.Events.CharacterIsDeadBroadcastEvent', (event) => {
      this.setState({
        isDead: event.isDead
      });
    });
  }

  componentDidUpdate(PrevProps) {
    if (this.state.canMove !== this.props.canMove) {
      this.setState({
        canMove: this.props.canMove,
      });
    }
  }

  componentWillUnmount() {
    Echo.leaveChannel('character-is-dead-' + this.props.userId);
  }

  dropDownList() {
    const portList = [];

    this.state.portList.forEach(port => {
      portList.push(<option key={port.id} value={port.id}>{port.name}</option>);
    });

    return portList;
  }

  handlePortChange(e) {
    this.setState({
      portId: e.target.value !== '' ? parseInt(e.target.value) : "",
    });
  }

  handleSetSail(portId) {
    if (this.props.characterIsDead) {
      return getServerMessage('character_is_dead');
    }

    const foundPort = this.state.portList.filter(pl => pl.id === portId)[0];

    this.setState({
      portId: ""
    });

    axios.post('/api/map/set-sail/' + portId + '/' + this.props.characterId, {
      current_port_id: this.state.currentPort.id,
      cost: foundPort.cost,
      time_out_value: foundPort.time,
    }).then((result) => {
      this.setState({
        currentPort: result.data.port_details.current_port,
        portList: result.data.port_details.port_list,
        adventures: result.data.adventure_details,
      }, () => {
        this.props.updatePlayerPosition(result.data.character_position_details);
        this.props.updateAdventure(this.state.adventures, [], null);
        this.props.updateCelestial(result.data.celestial_entities.length > 0 ? result.data.celestial_entities[0] : null);
        this.props.closePort();
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal()
        }
      }
    });
  }

  setSailButton(foundPort) {
    if (this.state.isDead) {
      return <span className="text-danger">You must revive.</span>
    }

    if (this.state.canMove) {
      if (foundPort.can_afford) {
        return <button className="btn btn-primary" onClick={() => this.handleSetSail(foundPort.id)}>Set Sail</button>
      }

      return <span className="text-danger">You don't have the gold.</span>
    }

    return <span className="text-danger">You must wait to move again.</span>
  }

  showPortDetails() {
    if (this.state.portId !== "") {
      const foundPort = this.state.portList.filter(pl => pl.id === this.state.portId)[0];

      return (
        <dl>
          <dt>Name:</dt>
          <dd>{foundPort.name}</dd>
          <dt>Cost To tavel:</dt>
          <dd>{foundPort.cost}</dd>
          <dt>Travel Timeout (minutes):</dt>
          <dd>{foundPort.time}</dd>
          <dt>Distance:</dt>
          <dd>{foundPort.distance}</dd>
          <dt>X/Y:</dt>
          <dd>{foundPort.x}/{foundPort.y}</dd>
          <dt>Set Sail?</dt>
          <dd>
            {this.setSailButton(foundPort)}
          </dd>
        </dl>
      );
    }
  }

  render() {
    return (
      <Row>
        <Col xs={12} sm={12} md={12} lg={12} xl={6}>
          <dl className="mb-2">
            <dt>Name:</dt>
            <dd>{this.state.currentPort.name}</dd>
            <dt>X/Y:</dt>
            <dd>{this.state.currentPort.x}/{this.state.currentPort.y}</dd>
            <dt>Set sail to:</dt>
            <dd>
              <select value={this.state.portId} onChange={this.handlePortChange.bind(this)}>
                <option value="">Please Select a port</option>
                {this.dropDownList()}
              </select>
            </dd>
          </dl>
        </Col>
        <Col xs={12} sm={12} md={12} lg={12} xl={6}>
          {this.showPortDetails()}
        </Col>
      </Row>
    );
  }
}
