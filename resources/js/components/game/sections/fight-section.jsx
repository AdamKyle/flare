import React from 'react';
import Attack from '../battle/attack/attack';
import Monster from '../battle/monster/monster';
import {getServerMessage} from '../helpers/server_message';
import ReviveSection from "./revive-section";

export default class FightSection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      character: this.props.character,
      monster: null,
      monsterCurrentHealth: null,
      monsterMaxHealth: null,
      characterMaxHealth: this.props.character.health,
      characterCurrentHealth: this.props.character.health,
      canAttack: this.props.character.can_attack,
      battleMessages: [],
      missCounter: 0,
    }

    this.timeOut = Echo.private('show-timeout-bar-' + this.props.userId);
    this.attackUpdate = Echo.private('update-character-attack-' + this.props.userId);
    this.isDead = Echo.private('character-is-dead-' + this.props.userId);
    this.attackStats = Echo.private('update-character-attack-' + this.props.userId);
  }

  componentDidMount() {
    this.attackUpdate.listen('Flare.Events.UpdateCharacterAttackBroadcastEvent', (event) => {
      this.setState({
        character: event.attack,
        characterMaxHealth: event.attack.health,
        showMessage: false,
      });
    });

    this.isDead.listen('Game.Core.Events.CharacterIsDeadBroadcastEvent', (event) => {
      let character = _.cloneDeep(this.state.character);

      character.is_dead = event.isDead;

      this.props.isCharacterDead(event.isDead);

      this.setState({
        character: character,
      });
    });

    this.attackStats.listen('Game.Core.Events.UpdateAttackStats', (event) => {
      this.setState({character: event.character});
    });

    this.timeOut.listen('Game.Core.Events.ShowTimeOutEvent', (event) => {
      this.setState({
        canAttack: event.canAttack,
      }, () => {
        this.props.canAttack(this.state.canAttack);
      });
    });
  }

  componentDidUpdate() {
    if (this.props.monster !== null) {
      this.setMonsterInfo();
    }
  }

  setMonsterInfo() {
    const monsterInfo = new Monster(this.props.monster);
    const health = monsterInfo.health();

    this.setState({
      battleMessages: [],
      missCounter: 0,
      monster: this.props.monster,
      monsterCurrentHealth: health,
      monsterMaxHealth: health,
    }, () => {
      this.props.setMonster(null)
    });
  }

  battleMessages() {
    return this.state.battleMessages.map((message) => {
      return <div key={message.message}><span className="battle-message">{message.message}</span> <br/></div>
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

    if (state.monsterCurrentHealth <= 0 || state.characterCurrentHealth <= 0) {
      axios.post('/api/battle-results/' + this.state.character.id, {
        is_character_dead: state.characterCurrentHealth <= 0,
        is_defender_dead: state.monsterCurrentHealth <= 0,
        defender_type: 'monster',
        monster_id: this.state.monster.id,
      }).then((response) => {
        let health = state.characterCurrentHealth;

        if (health >= 0) {
          health = this.state.characterMaxHealth;
        }

        this.setState({
          characterCurrentHealth: health,
          canAttack: false,
          monster: null,
        }, () => {
          this.props.setMonster(null);
        });
      }).catch((err) => {
        if (err.hasOwnProperty('response')) {
          const response = err.response;

          if (response.status === 429) {
            // Reload to show them their notification.
            return this.props.openTimeOutModal();
          }

          if (response.status === 401) {
            return location.reload();
          }
        }
      });;
    }
  }

  revive(data) {
    this.setState({
      character: data.character,
      characterMaxHealth: data.character.health,
      characterCurrentHealth: data.character.health,
    }, () => {
      this.props.isCharacterDead(data.character.is_dead);
    });
  }

  healthMeters() {
    if (this.state.monsterCurrentHealth <= 0 || this.state.monster === null) {
      return null;
    }

    let characterCurrentHealth = 0;

    if (this.state.characterCurrentHealth !== 0 && this.state.characterMaxHealth !== 0) {
      characterCurrentHealth = (this.state.characterCurrentHealth / this.state.characterMaxHealth) * 100;
    }

    const monsterCurrentHealth = (this.state.monsterCurrentHealth / this.state.monsterMaxHealth) * 100;

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

  render() {
    return (
      <>
        <hr/>
        <div className="battle-section text-center">
          {
            this.state.monsterCurrentHealth !== 0 && !this.state.character.is_dead && this.state.monster !== null ?
              <>
                <button className="btn btn-primary" onClick={this.attack.bind(this)}
                        disabled={this.props.isAdventuring}>Attack
                </button>
                {this.healthMeters()}
              </>
              : null
          }
          {
            this.state.character.is_dead ?
              <ReviveSection
                characterId={this.state.character.id}
                canAttack={this.state.canAttack}
                revive={this.revive.bind(this)}
                openTimeOutModal={this.props.openTimeOutModal}
                route={'/api/battle-revive/' + this.state.character.id}
              />
              : null
          }
          {this.battleMessages()}
        </div>
      </>
    );
  }
}
