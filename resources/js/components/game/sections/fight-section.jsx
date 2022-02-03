import React, {Fragment} from 'react';
import {OverlayTrigger, Tooltip} from 'react-bootstrap';
import { v4 as uuidv4 } from 'uuid';
import Attack from '../battle/attack/attack';
import Monster from '../battle/monster/monster';
import {getServerMessage} from '../helpers/server_message';
import ReviveSection from "./revive-section";
import Voidance from "../battle/attack/voidance";

const renderAttackToolTip = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    Attack.

    If you are a Fighter or Thief, you will attack with both weapons if you have them equipped.
    If you are not a Fighter or Thief, you will attack with the best weapon.
    If you have no weapon equipped, you will attack with 2% of your primary damage stat.
    Fighters will use 15% of their strength for weapons, 5% with out weapons. Where as Thieves and Rangers
    will use 5% of their primary damage stat and only 2% (including other classes) when attacking with no weapons.
  </Tooltip>
);

const renderCastingToolTip = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    Cast.

    We will attack with both spells. Heretics get an additional 30% of their primary damage stat as attack. Heretics can also cast with no
    spells equipped at 2% of their primary damage attack. Rangers, for healing, get 15% of their Chr while Prophets get 30% of their chr.
    If a prophet has no healing spell equipped, they still do their % of healing, how ever prophets special Double Heal will not fire
    with no healing spells equipped. Rangers can also heal for 15% of their chr with no healing spells equipped.
  </Tooltip>
);

const renderCastAndAttackToolTip = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    Cast and Attack.

    Will attack with spell in spell slot one and weapon in left hand as well as rings, artifacts and affixes.
    Uses Casting Accuracy for the spell and Accuracy for the weapon. If you have a bow equipped, we will use that
    as opposed to left/right hand. If you have no weapon equipped, we use 2% of your primary damage stat. If you are blocked at any time, both spell and
    weapon will be blocked.
  </Tooltip>
);

const renderAttackAndCastToolTip = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    Attack and Cast.

    Will attack with weapon in right hand and spell in spell slot two as well as rings, artifacts and affixes.
    Uses Accuracy for the weapon and then Casting Accuracy for the spell. If you have a bow equipped, we will use that
    as opposed to left/right hand. If you have no weapon equipped, we use 2% of your primary damage stat. If you are blocked at any time, both spell and
    weapon will be blocked.
  </Tooltip>
);

const renderDefendToolTip = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    Defend.

    Will use your armour class plus 5% of your strength. If you are a fighter and have at least one shield equipped
    you will add your class bonus to your defence. If you are not, we use your combined armour.
  </Tooltip>
);

export default class FightSection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      character: this.props.character,
      monster: null,
      monsterCurrentHealth: null,
      monsterMaxHealth: null,
      characterMaxHealth: null,
      characterCurrentHealth: null,
      canAttack: true,
      isDead: false,
      battleMessages: [],
      missCounter: 0,
      isCharacterVoided: false,
      isMonsterVoided: false,
      isMonsterDevoided: false,
      resetMonster: false,
    }

    this.timeOut = Echo.private('show-timeout-bar-' + this.props.userId);
    this.attackUpdate = Echo.private('update-character-attack-' + this.props.userId);
    this.isDead = Echo.private('character-is-dead-' + this.props.userId);
    this.attackStats = Echo.private('update-character-attack-' + this.props.userId);
    this.updateCharacterStatus = Echo.private('update-character-status-' + this.props.userId);

    this.battleMessagesBeforeFight = [];
    this.isMonsterVoided           = false;
    this.isMonsterDevoided         = false;
    this.isCharacterVoided         = false;
  }

  componentDidMount() {
    this.setState({
      isDead: this.props.character.is_dead,
    });

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
        this.props.canAttack(event.canAttack);
      });
    });

    this.updateCharacterStatus.listen('Game.Battle.Events.UpdateCharacterStatus', (event) => {
      this.setState({isDead: event.data.is_dead});

      if (!event.data.is_dead && this.props.monster !== null) {
        this.setState({characterCurrentHealth: null});

        this.setMonsterInfo(true);
      }
    });
  }

  componentDidUpdate(prevProps) {

    let stateMonster = this.state.monster;
    let propsMonster = this.props.monster;

    if (propsMonster !== null && stateMonster === null) {

      if (this.state.characterCurrentHealth !== null) {
        this.setState({characterCurrentHealth: null});
      }

      this.setMonsterInfo(false);
    } else if (propsMonster !== null && stateMonster !== null) {

      if (!stateMonster.hasOwnProperty('name')) {
        stateMonster = stateMonster.monster;
      }

      if (propsMonster.name !== stateMonster.name) {
        this.battleMessagesBeforeFight = [];
        this.isMonsterVoided           = false;
        this.isMonsterDevoided         = false;
        this.isCharacterVoided         = false;

        this.setState({
          battleMessages: [],
          monster: null,
          monsterCurrentHealth: null,
          characterCurrentHealth: null,
          characterMaxHealth: null,
          monsterMaxHealth: null,
        }, () => {
          this.setMonsterInfo(false);
        })
      }
    }
  }

  setMonsterInfo(keepHealth) {

    if (this.state.characterCurrentHealth !== null) {
      return;
    }

    if (!keepHealth) {
      this.setState({
        monsterCurrentHealth: null,
      })
    }

    const monsterInfo   = new Monster(this.props.monster);
    const voidance      = new Voidance();
    const character     = this.props.character;

    if (voidance.canPlayerDevoidEnemy(this.props.character.devouring_darkness) && !this.isMonsterDevoided) {
      this.battleMessagesBeforeFight.push({
        message: 'Magic crackles in the air, the darkness consumes the enemy. They are devoided!',
        class: 'action-fired'
      });

      this.isMonsterDevoided = true;
    }

    if (voidance.canVoidEnemy(this.props.character.devouring_light) && !this.isMonsterVoided) {
      this.battleMessagesBeforeFight.push({
        message: 'The light of the heavens shines through this darkness. The enemy is voided!',
        class: 'action-fired'
      });

      this.isMonsterVoided = true;
    }

    if (monsterInfo.canMonsterVoidPlayer() && !this.isCharacterVoided && !this.isMonsterDevoided) {
      this.battleMessagesBeforeFight.push({
        message: this.props.monster.name + ' has voided your enchantments! You feel much weaker!',
        class: 'enemy-action-fired'
      });

      this.isCharacterVoided = true;

    } else if (!this.isCharacterVoided) {

      let messages = monsterInfo.reduceResistances(character.resistance_reduction);

      if (messages.length > 0) {
        this.battleMessagesBeforeFight = [...this.battleMessagesBeforeFight, ...messages];
      }

      messages = monsterInfo.reduceSkills(character.skill_reduction);

      if (messages.length > 0) {
        this.battleMessagesBeforeFight = [...this.battleMessagesBeforeFight, ...messages];
      }

      messages = monsterInfo.reduceAllStats(character.stat_affixes);

      if (messages.length > 0) {
        this.battleMessagesBeforeFight = [...this.battleMessagesBeforeFight, ...messages];
      }
    }

    const health = monsterInfo.health();
    let characterHealth = this.props.character.health;

    if (this.isCharacterVoided) {
      characterHealth = this.props.character.voided_dur
    }

    console.log(this.props.charactr, characterHealth)

    this.setState({
      battleMessages: keepHealth ? this.state.battleMessages : [],
      missCounter: 0,
      monster: monsterInfo,
      characterCurrentHealth: characterHealth,
      characterMaxHealth: characterHealth,
      monsterCurrentHealth: keepHealth ? this.state.monsterCurrentHealth : health,
      monsterMaxHealth: keepHealth ? this.state.monsterMaxHealth : health,
    }, () => {
      console.log(this.state);
      this.props.setMonster(null)
    });
  }

  battleMessages() {
    return this.state.battleMessages.map((message) => {
      return <div key={uuidv4()}><span className={'battle-message ' + message.class}>{message.message}</span> <br/></div>
    });
  }

  setReviveInfo(data) {
    this.setState({
      characterMaxHealth: data.character_health,
      characterCurrentHealth: data.character_health,
    });
  }

  attack(attackType) {
    if (this.state.monster === null) {
      return getServerMessage('no_monster');
    }

    if (!this.state.canAttack) {
      return getServerMessage('cant_attack');
    }

    if (this.isCharacterVoided) {
      attackType = 'voided_' + attackType;
    } else if (!this.isMonsterDevoided && !this.isCharacterVoided) {
      if (this.state.monster.canMonsterVoidPlayer()) {
        this.battleMessagesBeforeFight.push({
          message: this.state.monster.monster.name + ' has voided your enchantments! You feel much weaker!',
          class: 'enemy-action-fired'
        });

        attackType = 'voided_' + attackType;

        this.isCharacterVoided = true;
      }
    }

    const attack = new Attack(
      this.state.characterCurrentHealth,
      this.state.monsterCurrentHealth,
      this.isCharacterVoided,
      this.isMonsterVoided,
    );

    const state = attack.attack(this.state.character, this.state.monster, true, 'player', attackType).getState()

    let messages = this.battleMessagesBeforeFight.filter((tag, index, array) =>
      array.findIndex(t => t.class == tag.class && t.message == tag.message) == index
    );

    state.battleMessages = [...messages, ...state.battleMessages].filter((bm) => !Array.isArray(bm))

    this.battleMessagesBeforeFight = [];

    if (state.characterCurrentHealth <= 0) {
      state.battleMessages.push({message: 'Death has come for you this day child! Resurrect to try again!', class: 'enemy-action-fired'});
    }

    if (state.characterCurrentHealth > this.state.characterCurrentHealth) {
      state.characterCurrentHealth = this.state.characterCurrentHealth;
    }

    if (state.monsterCurrentHealth > this.state.monsterMaxHealth) {
      state.monsterCurrentHealth = this.state.monsterMaxHealth;
    }

    this.setState(state);

    if (state.monsterCurrentHealth <= 0 || state.characterCurrentHealth <= 0) {
      let health = state.characterCurrentHealth;
      let monster = this.state.monster;

      if (health >= 0 && state.monsterCurrentHealth >= 0) {
        health = this.state.characterMaxHealth;
      } else if (health <= 0 && state.monsterCurrentHealth >= 0) {
        health = 0;
      } else {
        health = null;
      }

      if (state.monsterCurrentHealth <= 0) {
        monster = null;

        this.isMonsterDevoided = false;
        this.isMonsterVoided   = false;
        this.isCharacterVoided = false;
      }

      this.setState({
        characterCurrentHealth: health,
        characterMaxHealth: health,
        monsterCurrentHealth: monster !== null ? state.monsterCurrentHealth : null,
        monsterMaxHealth: monster !== null ? this.state.monsterMaxHealth : null,
        canAttack: false,
        monster: monster,
      }, () => {
        axios.post('/api/battle-results/' + this.state.character.id, {
          is_character_dead: state.characterCurrentHealth <= 0,
          is_defender_dead: state.monsterCurrentHealth <= 0,
          defender_type: 'monster',
          monster_id: this.state.monster.monster.id,
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
        });
      });
    }
  }

  revive(data, callback) {

    const isVoided = this.isCharacterVoided;

    this.setState({
      character: data.character,
      characterMaxHealth: isVoided ? data.character.voided_dur : data.character.health,
      characterCurrentHealth: isVoided ? data.character.voided_dur : data.character.health,
    }, () => {
      this.props.isCharacterDead(data.character.is_dead, callback);
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
               aria-valuemax={this.state.monsterMaxHealth}>{this.state.monster.getMonster().name}</div>
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
            this.state.monsterCurrentHealth > 0 && this.state.monster !== null ?
              <>
                {
                  !this.state.character.disable_pop_overs ?
                    <Fragment>
                      <OverlayTrigger
                        placement="right"
                        delay={{ show: 250, hide: 400 }}
                        overlay={renderAttackToolTip}
                      >
                        <button className="btn btn-attack mr-2"
                                disabled={this.props.isAdventuring || this.state.characterCurrentHealth === 0}
                                onClick={() => this.attack('attack')}
                        >
                          <i className="ra ra-sword"></i>
                        </button>
                      </OverlayTrigger>
                      <OverlayTrigger
                        placement="right"
                        delay={{ show: 250, hide: 400 }}
                        overlay={renderCastingToolTip}
                      >
                        <button className="btn btn-cast mr-2"
                                disabled={this.props.isAdventuring || this.state.characterCurrentHealth === 0}
                                onClick={() => this.attack('cast')}
                        >
                          <i className="ra ra-burning-book"></i>
                        </button>
                      </OverlayTrigger>
                      <OverlayTrigger
                        placement="right"
                        delay={{ show: 250, hide: 400 }}
                        overlay={renderCastAndAttackToolTip}
                      >
                        <button className="btn btn-cast-attack mr-2"
                                disabled={this.props.isAdventuring || this.state.characterCurrentHealth === 0}
                                onClick={() => this.attack('cast_and_attack')}
                        >
                          <i className="ra ra-lightning-sword"></i>
                        </button>
                      </OverlayTrigger>
                      <OverlayTrigger
                        placement="right"
                        delay={{ show: 250, hide: 400 }}
                        overlay={renderAttackAndCastToolTip}
                      >
                        <button className="btn btn-attack-cast mr-2"
                                disabled={this.props.isAdventuring || this.state.characterCurrentHealth === 0}
                                onClick={() => this.attack('attack_and_cast')}
                        >
                          <i className="ra ra-lightning-sword"></i>
                        </button>
                      </OverlayTrigger>
                      <OverlayTrigger
                        placement="right"
                        delay={{ show: 250, hide: 400 }}
                        overlay={renderDefendToolTip}
                      >
                        <button className="btn btn-defend"
                                disabled={this.props.isAdventuring || this.state.characterCurrentHealth === 0}
                                onClick={() => this.attack('defend')}
                        >
                          <i className="ra ra-round-shield"></i>
                        </button>
                      </OverlayTrigger>
                      {this.healthMeters()}
                    </Fragment>
                  :
                    <Fragment>
                      <button className="btn btn-attack mr-2"
                              disabled={this.props.isAdventuring || this.state.characterCurrentHealth === 0}
                              onClick={() => this.attack('attack')}
                      >
                        <i className="ra ra-sword"></i>
                      </button>
                      <button className="btn btn-cast mr-2"
                              disabled={this.props.isAdventuring || this.state.characterCurrentHealth === 0}
                              onClick={() => this.attack('cast')}
                      >
                        <i className="ra ra-burning-book"></i>
                      </button>
                      <button className="btn btn-cast-attack mr-2"
                              disabled={this.props.isAdventuring || this.state.characterCurrentHealth === 0}
                              onClick={() => this.attack('cast_and_attack')}
                      >
                        <i className="ra ra-lightning-sword"></i>
                      </button>
                      <button className="btn btn-attack-cast mr-2"
                              disabled={this.props.isAdventuring || this.state.characterCurrentHealth === 0}
                              onClick={() => this.attack('attack_and_cast')}
                      >
                        <i className="ra ra-lightning-sword"></i>
                      </button>
                      <button className="btn btn-defend"
                              disabled={this.props.isAdventuring || this.state.characterCurrentHealth === 0}
                              onClick={() => this.attack('defend')}
                      >
                        <i className="ra ra-round-shield"></i>
                      </button>
                      {this.healthMeters()}
                    </Fragment>
                }

              </>
              : null
          }
          {
            this.state.isDead ?
              <ReviveSection
                characterId={this.state.character.id}
                canAttack={this.state.canAttack}
                revive={this.revive.bind(this)}
                openTimeOutModal={this.props.openTimeOutModal}
                route={'/api/battle-revive/' + this.state.character.id}
                setReviveInfo={this.setReviveInfo.bind(this)}
              />
              : null
          }
          {this.battleMessages()}
        </div>
      </>
    );
  }
}
