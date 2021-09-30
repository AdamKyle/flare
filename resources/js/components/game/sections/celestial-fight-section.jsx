import React, {Fragment} from 'react';
import TimeOutBar from "../timeout/timeout-bar";
import ReviveSection from "./revive-section";

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

  attackCelestial() {
    this.setState({
      canAttack: false,
    }, () => {
      axios.post('/api/attack-celestial/' + this.props.characterId + '/' + this.props.celestialId).then((result) => {
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
      return <div key={index}><span className="battle-message">{log[0]}</span> <br/></div>
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
            <>
              <hr/>
              <div className="text-center">
                <div className="clearfix celestial-fight-actions">
                  {
                    this.state.battleIsOver ?
                      <button type="button"
                              className="btn btn-primary"
                              onClick={this.close.bind(this)}
                      >
                        Close
                      </button>
                    :
                      <Fragment>
                        <div className="float-left">
                          <button type="button" className="btn btn-primary" onClick={this.attackCelestial.bind(this)}
                                  disabled={this.props.isDead || !this.state.canAttack}>Attack!
                          </button>
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
                      </Fragment>
                  }
                </div>
                {
                  this.props.isDead ?
                    <>
                      <hr/>
                      <ReviveSection
                        characterId={this.props.characterId}
                        canAttack={this.state.canAttack}
                        revive={this.revive.bind(this)}
                        openTimeOutModal={this.props.openTimeOutModal}
                        route={'/api/celestial-revive/' + this.props.characterId}
                      />
                    </>
                    : null
                }
              </div>
              {this.healthMeters()}
              <div className="text-center m-auto">
                {this.logs()}
              </div>
            </>
        }
      </>
    )
  }
}
