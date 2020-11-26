import React from 'react';
import Monster from './monster/monster';
import Attack from './attack/attack';
import TimeOutBar from '../timeout/timeout-bar';
import {getServerMessage} from '../helpers/server_message';
import CraftingAction from '../crafting/crafting-action';
import EnchantingAction from '../enchanting/enchanting-action';

export default class BattleAction extends React.Component {

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
      disableAttack: false,
      itemsToCraft: null,
      isAdventuring: false,
    }

    this.timeOut         = Echo.private('show-timeout-bar-' + this.props.userId);
    this.topBar          = Echo.private('update-top-bar-' + this.props.userId);
    this.attackUpdate    = Echo.private('update-character-attack-' + this.props.userId);
    this.isDead          = Echo.private('character-is-dead-' + this.props.userId);
    this.adventureLogs   = Echo.private('update-adventure-logs-' + this.props.userId);
  }

  componentDidMount() {
    this.setState({
      character: this.props.character,
      monsters: this.props.monsters,
      characterMaxHealth: this.props.character.health,
      characterCurrentHealth: this.props.character.health,
      isLoading: false,
      canAttack: this.props.character.can_attack,
      timeRemaining: this.props.character.can_attack_again_at,
      showMessage: this.props.character.show_message,
      isAdventuring: !this.props.character.can_adventure,
    }, () => {
      this.props.isCharacterDead(this.props.character.is_dead);
      this.props.isCharacterAdventuring(!this.props.character.can_adventure);
      this.props.canAttack(this.state.canAttack);
    });

    this.isDead.listen('Game.Core.Events.CharacterIsDeadBroadcastEvent', (event) => {
      let character = _.cloneDeep(this.state.character);

      character.is_dead = event.isDead;

      this.props.isCharacterDead(event.isDead);

      this.setState({
        character: character,
      });
    });

    this.timeOut.listen('Game.Core.Events.ShowTimeOutEvent', (event) => {
      this.setState({
        canAttack:     event.canAttack,
      }, () => {
        this.props.canAttack(this.state.canAttack);
      });
    });

    this.topBar.listen('Game.Core.Events.UpdateTopBarBroadcastEvent', (event) => {
      const character = this.state.character;

      character.ac           =  event.characterSheet.ac;
      character.attack       =  event.characterSheet.attack;
      character.health       =  event.characterSheet.health;
      character.gold         =  event.characterSheet.gold;
      character.can_attack   =  this.state.character.can_attack;
      character.id           =  this.state.character.id;
      character.name         =  this.state.character.name;
      character.show_message =  this.state.character.show_message;
      character.skills       =  this.state.character.skills;

      this.setState({character: character});
    });

    this.attackUpdate.listen('Flare.Events.UpdateCharacterAttackBroadcastEvent', (event) => {
      this.setState({
        character: event.attack,
        characterMaxHealth: event.attack.health,
        showMessage: false,
      });
    });

    this.adventureLogs.listen('Game.Maps.Adventure.Events.UpdateAdventureLogsBroadcastEvent', (event) => {
      console.log(event);
      this.setState({
        isAdventuring: event.isAdventuring,
      }, () => {
        this.props.isCharacterAdventuring(event.isAdventuring)
      });
    });
  }

  componentDidUpdate(prevProps, prevState) {
    if (this.props.isDead !== prevProps.isDead) {
      let character = _.cloneDeep(this.state.character);
      character.is_dead = this.props.isDead;

      this.setState({
        character: character
      });
    }
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

    if (state.monsterCurrentHealth <= 0 || state.characterCurrentHealth <= 0) {
      axios.post('/api/battle-results/' + this.state.character.id, {
        is_character_dead: state.characterCurrentHealth <= 0,
        is_defender_dead: state.monsterCurrentHealth <= 0,
        defender_type: 'monster',
        monster_id: this.state.monster.id,
      }).then((result) => {
        let health = state.characterCurrentHealth;

        if (health >= 0) {
          health = this.state.characterMaxHealth;
        }

        this.setState({
          characterCurrentHealth: health,
          canAttack: false,
        });
      });
    }
  }

  revive() {
    if (!this.state.canAttack) {
      return getServerMessage('cant_attack');
    }
    
    axios.post('/api/battle-revive/' + this.state.character.id).then((result) => {
      this.setState({
        character: result.data.character,
        characterMaxHealth: result.data.character.health,
        characterCurrentHealth: result.data.character.health,
      }, () => {
        this.props.isCharacterDead(result.data.character.is_dead);
      });
    });
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

    let characterCurrentHealth = 0;

    if (this.state.characterCurrentHealth !== 0 && this.state.characterMaxHealth !== 0) {
      characterCurrentHealth = (this.state.characterCurrentHealth / this.state.characterMaxHealth) * 100;
    }

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

  renderActions() {
    return (
      <div className="col-md-10">
        {this.state.isAdventuring
         ?
         <div className="alert alert-warning" role="alert">
          You are currently adventuring and cannot fight any monsters or craft/enchant.
        </div>
         : 
         null
        }
        <div className="form-group row">
            <div className="col-md-8">
                <select className="form-control ml-3" id="monsters" name="monsters"
                  value={this.state.monster.hasOwnProperty('id') ? this.state.monster.id : 0}
                  onChange={this.updateActions.bind(this)}
                  disabled={this.state.character.is_dead || this.state.isAdventuring}>
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
              <div className="ml-4 mt-2">
                <TimeOutBar
                  cssClass={'character-timeout'}
                  readyCssClass={'character-ready'}
                  forSeconds={this.state.timeRemaining}
                  timeRemaining={this.state.timeRemaining}
                  channel={'show-timeout-bar-' + this.props.userId}
                  eventClass={'Game.Core.Events.ShowTimeOutEvent'}
                />
              </div>
            </div>
        </div>
        <CraftingAction
          isDead={this.state.character.is_dead}
          characterId={this.state.character.id}
          showCrafting={this.props.showCrafting}
          shouldChangeCraftingType={this.props.shouldChangeCraftingType}
          changeCraftingType={this.props.changeCraftingType}
          userId={this.props.userId}
          characterGold={this.state.character.gold}
          timeRemaining={this.props.character.can_craft_again_at}
          updateCanCraft={this.props.updateCanCraft}
          isAdventuring={this.state.isAdventuring}
        />
        {
          this.props.showEnchanting
          ?
          <EnchantingAction
            isDead={this.state.character.is_dead}
            characterId={this.state.character.id}
            showEnchanting={this.props.showEnchanting}
            shouldChangeCraftingType={this.props.shouldChangeCraftingType}
            changeCraftingType={this.props.changeCraftingType}
            userId={this.props.userId}
            characterGold={this.state.character.gold}
            timeRemaining={this.props.character.can_craft_again_at}
            updateCanCraft={this.props.updateCanCraft}
            isAdventuring={this.state.isAdventuring}
          />
          : null
        }
        <hr />
        <div className="battle-section text-center">
          {this.state.monsterCurrentHealth !== 0 && !this.state.character.is_dead
            ?
            <>
              <button className="btn btn-primary" onClick={this.attack.bind(this)} disabled={this.state.isAdventuring}>Attack</button>
              {this.healthMeters()}
            </>
            : null
          }
          {this.state.character.is_dead
            ? 
            <>
            <button className="btn btn-primary" onClick={this.revive.bind(this)}>Revive</button>
            <p className="mt-3">You are dead. Click revive to live again.</p>
            </>
            : null
          }
          {this.battleMessages()}
        </div>
      </div>
    )
  }

  render() {
    return (
      <>{this.state.isLoading ? 'Loading please wait ..' : this.renderActions() }</>
    )
  }
}
