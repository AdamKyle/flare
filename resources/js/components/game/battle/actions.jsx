import React from 'react';
import Monster from './monster/monster';
import Attack from './attack/attack';
import {getServerMessage} from '../helpers/server_message';

export default class Actions extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      character: null,
      monster: null,
      monsters: null,
      characterMaxHealth: 0,
      characterCurrentHealth: 0,
      monsterMaxHealth: 0,
      monsterCurrentHealth: 0,
      battleMessages: [],
      isLoading: true,
    }
  }

  componentDidMount() {
    axios.get('/api/actions', {
      params: {
        user_id: this.props.userId
      }
    }).then((result) => {
      this.setState({
        character: result.data.character.data,
        monsters: result.data.monsters,
        characterMaxHealth: result.data.character.data.health,
        characterCurrentHealth: result.data.character.data.health,
        isLoading: false,
      })
    });
  }

  updateActions(event) {
    const monster     = this.state.monsters.filter(monster => monster.id === parseInt(event.target.value))[0];
    const monsterInfo = new Monster(monster);
    const health      = monsterInfo.health();

    this.setState({
      monster: monster,
      monsterMaxHealth: health,
      monsterCurrentHealth: health,
    });
  }

  attack() {
    if (this.state.monster === null) {
      return getServerMessage('no_monster');
    }

    const attack = new Attack(
      this.state.character,
      this.state.monster,
      this.state.characterCurrentHealth,
      this.state.monsterCurrentHealth
    );

    const state = attack.attack(this.state.character, this.state.monster, true, 'player').getState();

    if (state.monsterCurrentHealth <= 0) {
      console.log('you win!');
    }

    this.setState(state);
  }

  monsterOptions() {
    return this.state.monsters.map((monster) => {
      return <option value={monster.id} key={monster.id}>{monster.name}</option>
    });
  }

  healthMeters() {
    if (this.state.monster === null) {
      return null;
    }

    const characterCurrentHealth = (this.state.characterCurrentHealth / this.state.characterMaxHealth) * 100;
    const monsterCurrentHealth   = (this.state.monsterCurrentHealth / this.state.monsterMaxHealth) * 100;

    return (
      <div className="health-meters mb-2 mt-2">
        <div className="progress character mb-2">
          <div className="progress-bar character-bar" role="progressbar"
            style={{width: characterCurrentHealth + '%'}}
            aria-valuenow={this.state.characterCurrentHealth} aria-valuemin="0"
            aria-valuemax={this.state.characterMaxHealth}>{this.state.character.name}</div>
        </div>
        <div className="progress monster mb-2">
          <div className="progress-bar monster-bar" role="progressbar"
            style={{width: monsterCurrentHealth + '%'}}
            aria-valuenow={this.state.monsterCurrentHealth} aria-valuemin="0"
            aria-valuemax={this.state.monsterMaxHealth}>{this.state.monster.name}</div>
        </div>
      </div>
    );
  }

  battleMessages() {
    return this.state.battleMessages.map((message) => {
      return <div key={message.message}><span className="battle-message">{message.message}</span> <br /></div>
    });
  }

  render() {
    return (
      <div className="card">
        <div className="card-body">
          {this.state.isLoading ? 'Loading please wait ..' : this.renderActions() }
        </div>
      </div>
    )
  }

  renderActions() {
    return (
      <div className="row justify-content-center">
        <div className="col-md-12">
          <div className="form-group row">
              <label htmlFor="monsters" className="col-md-4 col-form-label text-md-right">
                Choose a monster
              </label>

              <div className="col-md-6">
                  <select className="form-control" id="monsters" name="monsters"
                    onChange={this.updateActions.bind(this)}>
                      <option value="" key="0">Please select a monster</option>
                      {this.monsterOptions()}
                  </select>
              </div>
          </div>
          <hr />
          <div className="battle-section text-center">
            <button className="btn btn-primary" onClick={this.attack.bind(this)}>Attack</button>
            {this.healthMeters()}
            {this.battleMessages()}
          </div>
        </div>
      </div>
    )
  }
}
