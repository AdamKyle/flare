import React from 'react';
import Monster from './monster/monster';
import Attack from './attack/attack';
import TimeOutBar from '../timeout/timeout-bar';
import {getServerMessage} from '../helpers/server_message';
import { Dropdown } from 'react-bootstrap';

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
      disableAttack: false,
      showCrafting: false,
      craftingType: null,
      itemToCraft: null,
      itemsToCraft: null,
      timeRemainingCraft: null,
      canCraft: true,
    }

    this.echo            = Echo.private('show-timeout-bar-' + this.props.userId);
    this.topBar          = Echo.private('update-top-bar-' + this.props.userId);
    this.attackUpdate    = Echo.private('update-character-attack-' + this.props.userId);
    this.isDead          = Echo.private('character-is-dead-' + this.props.userId);
    this.craftingTimeOut = Echo.private('show-crafting-timeout-bar-' + this.props.userId);
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
        canCraft: result.data.character.data.can_craft,
        timeReminaingCraft: result.data.character.data.can_craft_again_at,
        showMessage: result.data.character.data.show_message,
      });
    });

    this.isDead.listen('Game.Battle.Events.CharacterIsDeadBroadcastEvent', (event) => {
      let character = _.cloneDeep(this.state.character);

      character.is_dead = event.isDead;

      this.setState({
        character: character,
      });
    });

    this.echo.listen('Game.Battle.Events.ShowTimeOutEvent', (event) => {
      this.setState({
        canAttack:     event.canAttack,
        showMessage:   false,
        timeRemaining: event.forLength,
      });
    });

    this.craftingTimeOut.listen('Game.Core.Events.ShowCraftingTimeOutEvent', (event) => {
      this.setState({
        canCraft:           event.canCraft,
        showMessage:        false,
        timeRemainingCraft: event.canCraft ? 0 : 10,
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
          canAttack: false
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
        character: result.data.character.data,
        characterMaxHealth: result.data.character.data.health,
        characterCurrentHealth: result.data.character.data.health,
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

  render() {
    return (
      <div className="card">
        <div className="card-body">
          {this.state.isLoading ? 'Loading please wait ..' : this.renderActions() }
        </div>
      </div>
    )
  }

  addCraftingAction() {
    this.setState({
      showCrafting: this.state.showCrafting ? false : true,
      itemToCraft: 0,
      craftingType: null,
      itemsToCraft: null,
    });
  }

  updateCraftingType(event) {
    this.setState({
      craftingType: event.target.value,
    }, () => {
      if (this.state.craftingType !== null) {
        axios.get('/api/crafting/' + this.state.character.id, {
          params: {
            crafting_type: this.state.craftingType
          }
        }).then((result) => {
          this.setState({
            itemsToCraft: result.data.items
          });
        });
      }
    });
  }

  buildCraftableItemsOptions() {
    if (this.state.itemsToCraft !== null) {
      return this.state.itemsToCraft.map((item) => {
        return <option key={item.id} value={item.id}>{item.name} --> Cost to craft: {item.cost}</option>
      });
    }
    
  }

  setItemToCraft(event) {
    this.setState({
      itemToCraft: parseInt(event.target.value),
    });
  }

  craft() {

    if (!this.state.canCraft) {
      return getServerMessage('cant_craft');
    }

    const foundItem = this.state.itemsToCraft.filter(item => item.id === this.state.itemToCraft)[0];

    if (foundItem.cost > this.state.character.gold) {
      return getServerMessage('not_enough_gold');
    }
    

    axios.post('/api/craft/' + this.state.character.id, {
      item_to_craft: this.state.itemToCraft,
      type: this.state.craftingType,
    }).then((result) => {
      this.setState({
        itemsToCraft: result.data.items
      });
    });
  }

  changeType() {
    this.setState({
      craftingType: null,
      itemsToCraft: null,
      itemToCraft: null,
    });
  }

  renderCraftingDropDowns() {
    if (this.state.showCrafting) {
      if (this.state.craftingType === null) {
        return (
          <select className="form-control ml-2 mt-2" id="crafting-type" name="crafting-type"
          value={0}
          onChange={this.updateCraftingType.bind(this)}
          disabled={this.state.character.is_dead}>
            <option value="" key="0">Please select a crafting type</option>
            <option value="Weapon" key="weapon">Weapon</option>
            <option value="Armour" key="armour">Armour</option>
            <option value="Spell" key="spell">Spell</option>
            <option value="Ring" key="ring">Ring</option>
            <option value="Artifact" key="artifact">Artifact</option>
          </select>
        );
      }

      return (
        <select className="form-control ml-2 mt-2" id="crafting" name="crafting"
        value={this.state.itemToCraft !== null ? this.state.itemToCraft : 0}
        onChange={this.setItemToCraft.bind(this)}
        disabled={this.state.character.is_dead}>
          <option value={0} key="0">Please select something to make</option>
          {this.buildCraftableItemsOptions()}
        </select>
      );
    }
  }

  renderActions() {

    return (
      <div className="row justify-content-center">
        <div className="col-md-12">
          <div className="form-group row">
              <div className="col-md-2">
                <Dropdown>
                  <Dropdown.Toggle variant="primary" id="dropdown-basic" size="sm" disabled={this.state.character.is_dead}>
                    Additional actions
                  </Dropdown.Toggle>

                  <Dropdown.Menu>
                    <Dropdown.Item onClick={this.addCraftingAction.bind(this)}>{this.state.showCrafting ? 'Remove Crafting' : 'Craft'}</Dropdown.Item>
                    {this.state.showCrafting
                     ?
                     <Dropdown.Item onClick={this.changeType.bind(this)}>Change Type</Dropdown.Item>
                     : null
                    }
                  </Dropdown.Menu>
                </Dropdown>
              </div>
              <div className="col-md-6">
                  <select className="form-control ml-2" id="monsters" name="monsters"
                    value={this.state.monster.hasOwnProperty('id') ? this.state.monster.id : 0}
                    onChange={this.updateActions.bind(this)}
                    disabled={this.state.character.is_dead}>
                      <option value="" key="0">Please select a monster</option>
                      {this.monsterOptions()}
                  </select>
                  {this.renderCraftingDropDowns()}
              </div>

              <div className="col-md-1">
                <button className="btn btn-primary"
                  type="button"
                  disabled={this.state.monster !== 0 ? false : true}
                  onClick={this.fightAgain.bind(this)}
                  >Again!</button>
                {(this.state.itemToCraft !== 0 && this.state.itemToCraft !== null && this.state.showCrafting) ? 
                <button className="btn btn-primary mt-2"
                type="button"
                disabled={this.state.character.is_dead}
                onClick={this.craft.bind(this)}
                >Craft!</button> : null}
                
              </div>

              <div className="col-md-3">
                <div className="ml-2 mt-2">
                  <TimeOutBar
                    userId={this.props.userId}
                    cssClass={'character-timeout'}
                    readyCssClass={'character-ready'}
                    eventName='Game.Battle.Events.ShowTimeOutEvent'
                    channel={'show-timeout-bar-'}
                    forSeconds={this.state.timeRemaining}
                    timeRemaining={this.state.timeRemaining}
                  />
                </div>
                <div className="ml-2 mt-2">
                  {(this.state.itemToCraft !== 0 && this.state.itemToCraft !== null && this.state.showCrafting)
                   ?
                   <TimeOutBar
                      userId={this.props.userId}
                      eventName='Game.Core.Events.ShowCraftingTimeOutEvent'
                      channel={'show-crafting-timeout-bar-'}
                      cssClass={'character-timeout mt-1'}
                      readyCssClass={'character-ready mt-4'}
                      forSeconds={10}
                      timeRemaining={this.state.timeRemainingCraft}
                    />
                  : null
                  }
                  
                </div>
              </div>
          </div>
          <hr />
          <div className="battle-section text-center">
            {this.state.monsterCurrentHealth !== 0 && !this.state.character.is_dead
              ?
              <>
                <button className="btn btn-primary" onClick={this.attack.bind(this)}>Attack</button>
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
      </div>
    )
  }
}
