import React from 'react';
import Monster from './monster/monster';
import Attack from './attack/attack';
import TimeOutBar from '../timeout/timeout-bar';
import {getServerMessage} from '../helpers/server_message';

export default class Actions extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      character: null,
      monster: 0,
      monsters: null,
      characterMaxHealth: 0,
      characterCurrentHealth: 0,
      monsterMaxHealth: 0,
      monsterCurrentHealth: 0,
      battleMessages: [],
      isLoading: true,
      canAttack: true,
      showMessage: false,
      timeRemaining: null,
    }

    this.echo         = Echo.private('show-timeout-bar-' + this.props.userId);
    this.topBar       = Echo.private('update-top-bar-' + this.props.userId);
    this.attackUpdate = Echo.private('update-character-attack-' + this.props.userId);
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
        canAttack: result.data.character.data.can_attack,
        timeRemaining: result.data.character.data.can_attack_again_at,
        showMessage: result.data.character.data.show_message,
      });
    });

    this.echo.listen('Game.Battle.Events.ShowTimeOutEvent', (event) => {
      this.setState({
        canAttack:   event.canAttack,
        showMessage: false,
      });
    });

    this.topBar.listen('Game.Battle.Events.UpdateTopBarBroadcastEvent', (event) => {
      const character = this.state.character;

        character.ac           =  event.characterSheet.data.ac,
        character.attack       =  event.characterSheet.data.attack,
        character.health       =  event.characterSheet.data.health,
        character.can_attack   =  this.state.character.can_attack,
        character.id           =  this.state.character.id,
        character.name         =  this.state.character.name,
        character.show_message =  this.state.character.show_message,
        character.skills       =  this.state.character.skills,

      this.setState({character: character});
    });

    this.attackUpdate.listen('Flare.Events.UpdateCharacterAttackBroadcastEvent', (event) => {
      this.setState({
        character: event.attack.data,
        characterMaxHealth: event.attack.data.health,
        showMessage: false,
      });
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
      battleMessages: [],
    });
  }

  fightAgain() {
    this.setState({
      monster: this.state.monster,
      monsterMaxHealth: this.state.monsterMaxHealth,
      monsterCurrentHealth: this.state.monsterMaxHealth,
      battleMessages: [],
    });
  }

  attack() {
    if (this.state.monster === null) {
      return getServerMessage('no_monster');
    }

    if (!this.state.canAttack) {
      return getServerMessage('cant_attack');
    }

    const attack = new Attack(
      this.state.character,
      this.state.monster,
      this.state.characterCurrentHealth,
      this.state.monsterCurrentHealth
    );

    const state = attack.attack(this.state.character, this.state.monster, true, 'player').getState();

    this.setState(state);

    if (state.monsterCurrentHealth <= 0) {
      axios.post('/api/battle-results/' + this.state.character.id, {
        is_character_dead: this.characterCurrentHealth === 0 ? true : false,
        is_defender_dead: true,
        defender_type: 'monster',
        monster_id: this.state.monster.id,
      }).then((result) => {
        this.setState({
          characterCurrentHealth: this.state.characterMaxHealth,
        });
      });
    }
  }

  monsterOptions() {
    return this.state.monsters.map((monster) => {
      return <option value={monster.id} key={monster.id}>{monster.name}</option>
    });
  }

  healthMeters() {
    if (this.state.monsterCurrentHealth <= 0) {
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
              <div className="col-md-2">
              </div>
              <div className="col-md-6">
                  <select className="form-control" id="monsters" name="monsters"
                    value={this.state.monster.hasOwnProperty('id') ? this.state.monster.id : 0}
                    onChange={this.updateActions.bind(this)}>
                      <option value="" key="0">Please select a monster</option>
                      {this.monsterOptions()}
                  </select>
              </div>

              <div className="col-md-1">
                <button className="btn btn-primary"
                  type="button"
                  disabled={this.state.monster !== 0 ? false : true}
                  onClick={this.fightAgain.bind(this)}
                  >Again!</button>
              </div>

              <div className="col-md-3">
                <div className="ml-2 mt-2">
                <TimeOutBar
                  userId={this.props.userId}
                  eventName='Game.Battle.Events.ShowTimeOutEvent'
                  channel={'show-timeout-bar-'}
                  cssClass={'character-timeout'}
                  readyCssClass={'character-ready'}
                  forSeconds={10}
                  timeRemaining={this.state.timeRemaining}
                />
                </div>
              </div>
          </div>
          <hr />
          <div className="battle-section text-center">
            {this.state.monsterCurrentHealth !== 0
              ?
              <>
                <button className="btn btn-primary" onClick={this.attack.bind(this)}>Attack</button>
                {this.healthMeters()}
              </>
              : null
            }
            {this.battleMessages()}
          </div>
        </div>
      </div>
    )
  }
}
