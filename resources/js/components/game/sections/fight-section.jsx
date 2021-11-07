import React from 'react';
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

    If you are a fighter or thief, you will attack with both weapons if you have them equipped.
    If you are not a fighter, you will attack with the best weapon.
    If you have no weapon equipped, you will attack with 2% of your primary damage stat.
    Fighters and Thieves will use 15% of their primary damage stat (str/dex) on top of their combined weapon
    damage. Other classes will not use any bonuses from their class, unless, they have no weapon equipped.
  </Tooltip>
);

const renderCastingToolTip = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    Cast.

    We will attack with both spells. Heretics get an additional 32% of your primary damage stat while Prophets get 17%. If you have healing spells,
    prophets will get 30% towards healing spells and Rangers get 15% (of their chr) towards healing spells. If you have no spells equipped
    and are a prophet or heretic, you will attack with 2% of your primary damage stat.
    Prophets and Rangers can heal for 30% and 15% (respectively) of their chr even with no spell equipped.
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

    Will use your armour class plus 5% of your strength. If you're a Fighter, we use 15% of your strength.
    Only your affixes, rings and artifacts will fire during your round. During the enemies phase you will
    have a chance to block them (including their spells) assuming you are not entranced.
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
      canAttack: this.props.character.can_attack,
      battleMessages: [],
      missCounter: 0,
      isCharacterVoided: false,
      isMonsterVoided: false,
      isMonsterDevoided: false,
    }

    this.timeOut = Echo.private('show-timeout-bar-' + this.props.userId);
    this.attackUpdate = Echo.private('update-character-attack-' + this.props.userId);
    this.isDead = Echo.private('character-is-dead-' + this.props.userId);
    this.attackStats = Echo.private('update-character-attack-' + this.props.userId);

    this.battleMessagesBeforeFight = [];
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

    let stateMonster = this.state.monster;
    let propsMonster = this.props.monster;

    if (propsMonster !== null && stateMonster === null) {

      if (this.state.characterCurrentHealth !== null) {
        this.setState({characterCurrentHealth: null});
      }

      this.setMonsterInfo();
    } else if (propsMonster !== null && stateMonster !== null) {

      if (!stateMonster.hasOwnProperty('name')) {
        stateMonster = stateMonster.monster;
      }

      if (propsMonster.name !== stateMonster.name) {
        this.battleMessagesBeforeFight = [];

        this.setState({
          battleMessages: [],
          monster: null,
          monsterCurrentHealth: null,
          characterCurrentHealth: null,
          characterMaxHealth: null,
          monsterMaxHealth: null,
          isCharacterVoided: false,
          isMonsterVoided: false,
          isMonsterDevoided: false,
        }, () => {
          this.setMonsterInfo();
        })
      }
    }
  }

  setMonsterInfo() {

    if (this.state.characterCurrentHealth !== null || this.state.monsterCurrentHealth !== null) {

      return;
    }

    const monsterInfo   = new Monster(this.props.monster);
    const voidance      = new Voidance();
    const character     = this.props.character;
    let isVoided        = false;
    let monsterVoided   = false;
    let monsterDevoided = false;

    if (voidance.canPlayerDevoidEnemy(this.props.character.devouring_darkness) && !monsterDevoided) {
      this.battleMessagesBeforeFight.push({
        message: 'Magic crackles in the air, the darkness consumes the enemy. They are devoided!',
        class: 'action-fired'
      });

      monsterDevoided = true;
    }

    if (voidance.canVoidEnemy(this.props.character.devouring_light) && !monsterVoided) {
      this.battleMessagesBeforeFight.push({
        message: 'The light of the heavens shines through this darkness. The enemy is voided!',
        class: 'action-fired'
      });

      monsterVoided = true;
    }

    if (monsterInfo.canMonsterVoidPlayer() && !this.state.isCharacterVoided && !monsterVoided) {
      this.battleMessagesBeforeFight.push({
        message: this.props.monster.name + ' has voided your enchantments! You feel much weaker!',
        class: 'enemy-action-fired'
      });

      isVoided = true;

    } else if (!this.state.isCharacterVoided) {
      let messages = monsterInfo.reduceAllStats(character.stat_affixes);

      if (messages.length > 0) {
        this.battleMessagesBeforeFight = [...this.battleMessagesBeforeFight, ...messages];
      }

      messages = monsterInfo.reduceSkills(character.skill_reduction);

      if (messages.length > 0) {
        this.battleMessagesBeforeFight = [...this.battleMessagesBeforeFight, ...messages];
      }

      messages = monsterInfo.reduceResistances(character.resistance_reduction);

      if (messages.length > 0) {
        this.battleMessagesBeforeFight = [...this.battleMessagesBeforeFight, ...messages];
      }
    }

    const health = monsterInfo.health();
    let characterHealth = this.props.character.health;

    if (isVoided) {
      characterHealth = this.props.character.voided_dur
    }

    this.setState({
      battleMessages: [],
      missCounter: 0,
      monster: monsterInfo,
      monsterCurrentHealth: health,
      characterCurrentHealth: characterHealth,
      characterMaxHealth: characterHealth,
      monsterMaxHealth: health,
      isCharacterVoided: isVoided,
      isMonsterVoided: monsterVoided,
      isMonsterDevoided: monsterDevoided,
    }, () => {
      this.props.setMonster(null)
    });
  }

  battleMessages() {
    return this.state.battleMessages.map((message) => {
      return <div key={uuidv4()}><span className={'battle-message ' + message.class}>{message.message}</span> <br/></div>
    });
  }

  setReviveInfo(health) {
    this.setState({
      characterMaxHealth: health,
      characterCurrentHealth: health,
    });
  }

  attack(attackType) {
    if (this.state.monster === null) {
      return getServerMessage('no_monster');
    }

    if (!this.state.canAttack) {
      return getServerMessage('cant_attack');
    }

    if (this.state.isCharacterVoided) {
      attackType = 'voided_' + attackType;
    } else if (!this.state.isMonsterDevoided && !this.state.isCharacterVoided) {

      if (this.state.monster.canMonsterVoidPlayer()) {

        this.battleMessagesBeforeFight.push({
          message: this.state.monster.monster.name + ' has voided your enchantments! You feel much weaker!',
          class: 'enemy-action-fired'
        });

        attackType = 'voided_' + attackType;

        this.setState({isCharacterVoided: true});
      }
    }

    const attack = new Attack(
      this.state.characterCurrentHealth,
      this.state.monsterCurrentHealth,
      this.state.isCharacterVoided,
      this.state.isMonsterVoided,
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
      axios.post('/api/battle-results/' + this.state.character.id, {
        is_character_dead: state.characterCurrentHealth <= 0,
        is_defender_dead: state.monsterCurrentHealth <= 0,
        defender_type: 'monster',
        monster_id: this.state.monster.monster.id,
      }).then(() => {
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
        }

        this.setState({
          characterCurrentHealth: health,
          characterMaxHealth: health,
          monsterCurrentHealth: monster !== null ? state.monsterCurrentHealth : null,
          monsterMaxHealth: monster !== null ? this.state.monsterMaxHealth : null,
          canAttack: false,
          monster: monster,
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
      });
    }
  }

  revive(data, callback) {

    const isVoided = this.state.isCharacterVoided;

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
