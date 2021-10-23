import React, {Fragment} from 'react';
import TimeOutBar from "../timeout/timeout-bar";
import ReviveSection from "./revive-section";
import {OverlayTrigger, Tooltip} from "react-bootstrap";

const renderAttackToolTip = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    If you are a fighter, you will attack with both weapons if you have them equipped.
    If you are not a fighter, you will attack with the best weapon.
    If you have no weapon equipped, you will attack with 2% of your primary damage stat.
  </Tooltip>
);

const renderCastingToolTip = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    We will attack with both spells. Casters get an additional 15% of your primary damage stat. If you have healing spells,
    prophets will get 30% towards healing spells and Rangers get 15% towards healing spells. If you have no spells equipped
    and are a prophet or heretic, you will attack with 2% of your primary damage stat.
    Prophets and Rangers can heal for 30% and 15% (respectively) of their chr even with no spell equipped.
  </Tooltip>
);

const renderCastAndAttackToolTip = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    Will attack with spell in spell slot one and weapon in left hand as well as rings, artifacts and affixes.
    Uses Casting Accuracy for the spell and Accuracy for the weapon. If you have a bow equipped, we will use that
    as opposed to left/right hand. If you have no weapon equipped, we use 2% of your primary damage stat. If you are blocked at any time, both spell and
    weapon will be blocked.
  </Tooltip>
);

const renderAttackAndCastToolTip = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    Will attack with weapon in right hand and spell in spell slot two as well as rings, artifacts and affixes.
    Uses Accuracy for the weapon and then Casting Accuracy for the spell. If you have a bow equipped, we will use that
    as opposed to left/right hand. If you have no weapon equipped, we use 2% of your primary damage stat. If you are blocked at any time, both spell and
    weapon will be blocked.
  </Tooltip>
);

const renderDefendToolTip = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    Will use your armour class plus 5% of your strength. If you're a Fighter, we use 15% of your strength.
    Only your affixes, rings and artifacts will fire during your round. During the enemies phase you will
    have a chance to block them (including their spells) assuming you are not entranced.
  </Tooltip>
);

export default class CelestialFightSection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      logs: [],
      characterCurrentHealth: 0,
      characterMaxHealth: 0,
      monsterCurrentHealth: 0,
      monsterMaxHealth: 0,
      canAttack: true,
      loading: true,
      battleIsOver: false,
    }

    this.celestialUpdates = Echo.join('celestial-fight-changes');
  }

  componentDidMount() {
    axios.get('/api/celestial-fight/' + this.props.characterId + '/' + this.props.celestialId).then((result) => {
      if (result.data.hasOwnProperty('fight')) {
        this.setState({
          characterCurrentHealth: result.data.fight.character.current_health,
          characterMaxHealth: result.data.fight.character.max_health,
          monsterCurrentHealth: result.data.fight.monster.current_health,
          monsterMaxHealth: result.data.fight.monster.max_health,
          loading: false,
        });
      }
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal();
        }
      }
    });

    this.celestialUpdates.listen('Game.Battle.Events.UpdateCelestialFight', (event) => {
      if (event.data.close_fight) {
        this.setState({
          battleIsOver: true,
        });
      }

      this.setState({
        monsterCurrentHealth: event.data.monster_current_health,
      });
    });
  }

  attackCelestial(attackType) {
    this.setState({
      canAttack: false,
    }, () => {
      axios.post('/api/attack-celestial/' + this.props.characterId + '/' + this.props.celestialId, {
        attack_type: attackType
      }).then((result) => {
        if (result.data.hasOwnProperty('battle_over')) {
          this.setState({
            battleIsOver: true,
            logs: result.data.logs,
            monsterCurrentHealth: 0,
          });
        } else {
          this.setState({
            characterCurrentHealth: result.data.fight.character.current_health,
            monsterCurrentHealth: result.data.fight.monster.current_health,
            logs: result.data.logs,
          })
        }
      }).catch((err) => {
        if (err.hasOwnProperty('response')) {
          const response = err.response;

          if (response.status === 401) {
            return location.reload();
          }

          if (response.status === 429) {
            return this.props.openTimeOutModal();
          }
        }
      });
    });
  }

  close() {
    this.props.switchBattleAction('battle-action');
  }

  revive(data) {
    this.setState({
      characterCurrentHealth: data.fight.character.current_health,
      monsterCurrentHealth: data.fight.monster.current_health,
    });
  }

  updateCanAttack(canAttack) {
    this.setState({
      canAttack: canAttack
    });
  }

  healthMeters() {
    if (this.state.monsterCurrentHealth <= 0) {
      return null;
    }

    let characterCurrentHealth = 0;

    if (this.state.characterCurrentHealth !== 0) {
      characterCurrentHealth = (this.state.characterCurrentHealth / this.state.characterMaxHealth) * 100;
    }

    const monsterCurrentHealth = (this.state.monsterCurrentHealth / this.state.monsterMaxHealth) * 100;

    return (
      <div className="health-meters mb-2 mt-2">
        <div className="progress character mb-2">
          <div className="progress-bar character-bar" role="progressbar"
               style={{width: characterCurrentHealth + '%'}}
               aria-valuenow={this.state.characterCurrentHealth} aria-valuemin="0"
               aria-valuemax={this.state.characterMaxHealth}>{this.props.characterName}</div>
        </div>
        <div className="progress monster mb-2">
          <div className="progress-bar monster-bar" role="progressbar"
               style={{width: monsterCurrentHealth + '%'}}
               aria-valuenow={this.state.monsterCurrentHealth} aria-valuemin="0"
               aria-valuemax={this.state.monsterMaxHealth}>{this.props.monsterName}</div>
        </div>
      </div>
    );
  }

  logs() {
    return this.state.logs.map((log, index) => {
      return <div key={index}><span className={log.class}>{log.message}</span> <br/></div>
    });
  }

  render() {
    return (
      <>
        {
          this.state.loading ?
            <div className="progress" style={{position: 'relative', height: '5px'}}>
              <div className="progress-bar progress-bar-striped indeterminate">
              </div>
            </div>
          :
            this.props.isDead ?
              <div className="text-center">
                <div className="clearfix container-sm" style={{maxWidth: 400}}>
                  <div className="float-left">
                    <ReviveSection
                      characterId={this.props.characterId}
                      canAttack={this.state.canAttack}
                      revive={this.revive.bind(this)}
                      openTimeOutModal={this.props.openTimeOutModal}
                      route={'/api/celestial-revive/' + this.props.characterId}
                    />
                  </div>
                  <div className="float-right">
                    <TimeOutBar
                      cssClass={'character-timeout'}
                      readyCssClass={'character-ready'}
                      timeRemaining={0}
                      channel={'show-timeout-bar-' + this.props.userId}
                      eventClass={'Game.Core.Events.ShowTimeOutEvent'}
                      updateCanAttack={this.updateCanAttack.bind(this)}
                    />
                  </div>
                </div>


              </div>
            :
              this.state.battleIsOver ?
                <div className="text-center">
                  <button type="button"
                          className="btn btn-primary"
                          onClick={this.close.bind(this)}
                  >
                    Close
                  </button>
                </div>
              :
                <>
                  <div className="text-center container-sm" style={{maxWidth: 400}}>
                    <OverlayTrigger
                      placement="right"
                      delay={{ show: 250, hide: 400 }}
                      overlay={renderAttackToolTip}
                    >
                      <button className="btn btn-attack mr-2"
                              disabled={this.props.isAdventuring}
                              onClick={() => this.attackCelestial('attack')}
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
                              disabled={this.props.isAdventuring}
                              onClick={() => this.attackCelestial('cast')}
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
                              disabled={this.props.isAdventuring}
                              onClick={() => this.attackCelestial('cast_and_attack')}
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
                              disabled={this.props.isAdventuring}
                              onClick={() => this.attackCelestial('attack_and_cast')}
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
                              disabled={this.props.isAdventuring}
                              onClick={() => this.attackCelestial('defend')}
                      >
                        <i className="ra ra-round-shield"></i>
                      </button>
                    </OverlayTrigger>
                  </div>
                </>
        }
        {this.healthMeters()}
        <hr />
        <div className="text-center m-auto">
          {this.logs()}
        </div>
      </>
    );
  }
}
