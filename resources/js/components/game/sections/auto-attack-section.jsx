import React from 'react';
import {Col, Tab, Tabs} from "react-bootstrap";
import AlertWarning from "../components/base/alert-warning";
import ExplorationTimeOutBar from "../timeout/exploration-timeout-bar";
import AlertError from "../components/base/alert-error";
import AttackType from "../battle/attack/attack-type";
import AlertSuccess from "../components/base/alert-success";

export default class AutoAttackSection extends React.Component {

  constructor(props) {
    super(props);

    const skillInTraining = this.props.character.skills.filter((skill) => skill.is_training);

    this.state = {
      character: this.props.character,
      monsters: this.props.monsters,
      isDead: this.props.character.is_dead,
      errorMessage: null,
      successMessage: null,
      successTitle: null,
      isLoading: true,
      isStarting: false,
      isStopping: false,
      timeRemaining: null,
      showSkillSection: false,
      showMoveDownTheList: false,
      trainableSkills: this.props.character.skills.filter((skill) => skill.can_train),
      attackMessages: [],
      params: {
        id: 0,
        skill_id: skillInTraining.length > 0 ? skillInTraining[0].id : null,
        xp_towards: skillInTraining.length > 0 ? skillInTraining[0].xp_towards : null,
        auto_attack_length: null,
        move_down_the_list_every: null,
        selected_monster_id: null,
        attack_type: AttackType.ATTACK,
      }
    }

    this.automation               = Echo.private('exploration-timeout-' + this.props.userId);
    this.automationAttackMessages = Echo.private('exploration-attack-messages-' + this.props.userId);
    this.automationAttackDetails  = Echo.private('exploration-attack-details-' + this.props.userId);
    this.isDead                   = Echo.private('character-is-dead-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/exploration-automations/' + this.props.character.id).then((result) => {
      this.setState({isLoading: false});

      const automation = result.data.automation;

      if (automation.hasOwnProperty('skill_id')) {
        this.setState({
          params: result.data.automation,
          timeRemaining: result.data.automation.auto_attack_length
        });
      }
    }).catch((err) => {
      this.setState({isLoading: false});

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

    this.automation.listen('Game.Exploration.Events.ExplorationTimeOut', (event) => {
      this.setState({
        errorMessage: null,
        timeRemaining: event.forLength,
      })
    });

    this.automationAttackMessages.listen('Game.Exploration.Events.ExplorationAttackMessage', (event) => {
      this.setState({
        errorMessage: null,
        attackMessages: event.messages,
      })
    });

    this.automationAttackDetails.listen('Game.Exploration.Events.ExplorationDetails', (event) => {
      this.setState({
        errorMessage: null,
        params: event.details,
      })
    });

    this.isDead.listen('Game.Core.Events.CharacterIsDeadBroadcastEvent', (event) => {
      this.setState({
        errorMessage: null,
        isDead: event.isDead,
      });
    });
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    if (this.state.monsters[0].name !== this.props.monsters[0].name) {
      this.setState({
        monsters: this.props.monsters,
      });
    }
  }

  updateSelectedMonster(event) {
    const params = _.cloneDeep(this.state.params);

    params.selected_monster_id = parseInt(event.target.value) || 0;

    this.setState({
      params: params,
    });
  }

  selectAttackType(event) {
    const params = _.cloneDeep(this.state.params);

    params.attack_type = event.target.value;

    this.setState({
      params: params,
    });
  }

  updateSelectedSkill(event) {
    const params = _.cloneDeep(this.state.params);

    params.skill_id = parseInt(event.target.value) || 0;

    this.setState({
      params: params,
    });
  }

  showSkillChangeSection(event) {
    const value  = event.target.checked;
    const params = _.cloneDeep(this.state.params);

    if (!value) {
      const skillInTraining = this.props.character.skills.filter((skill) => skill.is_training);

      params.skill_id   = skillInTraining.length > 0 ? skillInTraining[0].id : null;
      params.xp_towards = skillInTraining.length > 0 ? skillInTraining[0].xp_towards : null;
    }

    this.setState({
      showSkillSection: event.target.checked,
      params: params
    });
  }

  showMoveDownTheList(event) {
    const value  = event.target.checked;
    const params = _.cloneDeep(this.state.params);

    if (!value) {
      params.move_down_the_list_every = null;
    }

    this.setState({
      showMoveDownTheList: value,
      params: params,
    });
  }

  updateSelectedXPForSkill(event) {
    const params = _.cloneDeep(this.state.params);

    params.xp_towards = parseFloat(event.target.value) || 0.0;

    this.setState({
      params: params,
    });
  }

  updateMoveDownListEvery(event) {
    const params = _.cloneDeep(this.state.params);

    const value = parseInt(event.target.value) || 0;

    if (value > 0) {
      params.move_down_the_list_every = value;

      this.setState({
        params: params,
      });
    }
  }

  updateAutoAttackLength(event) {
    const params = _.cloneDeep(this.state.params);

    params.auto_attack_length = parseInt(event.target.value) || 0;

    this.setState({
      params: params,
    });
  }

  disabledInput() {
    return this.state.isDead;
  }

  monsterOptions() {
    return this.state.monsters.map((monster) => {
      return <option value={monster.id} key={monster.id}>{monster.name}</option>
    });
  }

  skillOptions() {
    return this.state.trainableSkills.map((skill) => {
      return <option value={skill.id} key={skill.id}>{skill.name}</option>
    });
  }

  closeSuccess() {
    this.setState({
      successMessage: null,
    });
  }

  displayAttackMessages() {
    return this.state.attackMessages.map((message) => {
      return <div className={message.class}>{message.message}</div>
    });
  }

  beginFight() {

    if (this.state.params.selected_monster_id === null) {
      this.setState({
        errorMessage: 'You must select a monster'
      });

      return;
    }

    if (this.state.params.auto_attack_length === null) {
      this.setState({
        errorMessage: 'How long should this exploration go for? Check Advanced tab and configure a length.'
      });

      return;
    }

    if (this.state.params.attack_type === null) {
      this.setState({
        errorMessage: 'Please select an attack type.'
      });

      return;
    }

    this.setState({
      errorMessage: null,
      successMessage: null,
      isStarting: true,
      attackMessages: [],
    }, () => {
      axios.post('/api/exploration/'+this.props.character.id+'/start', this.state.params).then((result) => {
        this.setState({
          isStarting: false,
          successMessage: result.data.message,
          successTitle: 'It has begun!',
          params: {...this.state.params, ...{id: result.data.id}},
        });
      }).catch((err) => {
        this.setState({isLoading: false});

        if (err.hasOwnProperty('response')) {
          const response = err.response;

          if (response.status === 401) {
            return location.reload();
          }

          if (response.status === 429) {
            return this.props.openTimeOutModal();
          }

          if (response.status === 422) {
            this.setState({
              errorMessage: response.data.message,
            });
          }
        }
      });
    });
  }

  stopAutomation() {
    this.setState({
      errorMessage: null,
      successMessage: null,
      isStopping: true,
    }, () => {
      axios.post('/api/exploration/'+this.state.params.id+'/'+this.props.character.id+'/stop').then((result) => {
        this.setState({
          isStopping: false,
          successMessage: result.data.message,
          successTitle: 'Stopping ...'
        });
      }).catch((err) => {
        if (err.hasOwnProperty('response')) {
          const response = err.response;

          if (response.status === 401) {
            return location.reload();
          }

          if (response.status === 429) {
            return this.props.openTimeOutModal();
          }

          if (response.status == 404) {
            this.setState({
              errorMessage: 'Exploration has stopped.'
            })
          }
        }
      });
    });
  }

  render() {
    if (this.state.isLoading) {
      return (
        <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
          <div className="progress-bar progress-bar-striped indeterminate">
          </div>
        </div>
      );
    }

    return (
      <div className="mt-4">
        <Tabs defaultActiveKey="general" id="auto-general-config-tab-section">
          <Tab eventKey="general" title="General">
            <div className="row mt-4">
              <Col lg={12} xl={6}>
                {
                  this.state.errorMessage !== null ?
                    <AlertError icon={"fas fa-exclamation-circle"} title={'Oops!'}>
                      <p>
                        {this.state.errorMessage}
                      </p>
                    </AlertError>
                  : null
                }
                {
                  this.state.isDead ?
                    <AlertError icon={"fas fa-skull-crossbones"} title={'Uh... You died!'}>
                      <p>
                        Exploration has been stopped. You died. Please revive.
                      </p>
                    </AlertError>
                    : null
                }
                {
                  this.state.successMessage !== null ?
                    <AlertSuccess icon={"fas fa-check-circle"}
                                  title={this.state.successTitle}
                                  showClose={true}
                                  closeAlert={this.closeSuccess.bind(this)}
                    >
                      <p>
                        {this.state.successMessage}
                      </p>
                    </AlertSuccess>
                  : null
                }
                <div className="form-group">
                  <label htmlFor="monsters-auto-attack">Select Monster</label>
                  <select className="form-control monster-select" id="monsters-auto-attack" name="monsters-auto-attack"
                          value={this.state.params.selected_monster_id}
                          onChange={this.updateSelectedMonster.bind(this)}
                          disabled={this.disabledInput() || this.props.attackAutomationIsRunning}>
                    <option value="0" key="-1">Please select a monster</option>
                    {this.monsterOptions()}
                  </select>
                </div>
                <div className="form-group">
                  <label htmlFor="attack-type">Attack Type</label>
                  <select className="form-control"
                          id="attack-type"
                          value={this.state.params.attack_type}
                          onChange={this.selectAttackType.bind(this)}
                          disabled={this.props.attackAutomationIsRunning|| this.disabledInput()}
                  >
                    <option value={AttackType.ATTACK}>Attack</option>
                    <option value={AttackType.CAST}>Cast</option>
                    <option value={AttackType.CAST_AND_ATTACK}>Cast then Attack</option>
                    <option value={AttackType.ATTACK_AND_CAST}>Attack then Cast</option>
                    <option value={AttackType.DEFEND}>Defend</option>
                  </select>
                  <small id="attack-type-help" className="form-text text-muted">
                    Each attack type corresponds to the attack button from drop down critters.
                  </small>
                </div>
                <button className="btn btn-primary mt-3"
                        onClick={this.beginFight.bind(this)}
                        disabled={this.state.isStarting || this.state.isStopping || this.props.attackAutomationIsRunning || this.props.isDead}
                >
                  {this.state.isStarting ? <i className="fas fa-spinner fa-spin"></i> : null} Begin!
                </button>
                {
                  this.props.attackAutomationIsRunning ?
                    <button className="btn btn-danger ml-2 mt-3"
                            onClick={this.stopAutomation.bind(this)}
                            disabled={this.state.isLoading || this.state.isStopping || this.state.isStarting || this.props.isDead}
                    >
                      {this.state.isStopping ? <i className="fas fa-spinner fa-spin"></i> : null} Stop!
                    </button>
                  : null
                }
              </Col>

              <Col lg={12} xl={6}>
                <div className="tw-text-center">
                  {
                    this.state.timeRemaining > 0 ?
                      <ExplorationTimeOutBar
                        innerTimerCss={'auto-attack'}
                        readyCssClass={'character-ready'}
                        timeRemaining={this.state.timeRemaining}
                        channel={'exploration-timeout-' + this.props.userId}
                        eventClass={'Game.Exploration.Events.ExplorationTimeOut'}
                      />
                    :
                      <div className="character-ready">
                        Ready!
                      </div>
                  }
                  <div className="tw-mt-2">
                    Every <strong>ten minutes</strong> the Exploration Log tab will update with results from the previous battle. Keep an eye on that for updates!
                    The timer above is how long you have left on this current automation. <strong>You can log out and this will still process</strong>.
                  </div>
                  <div className="tw-mt-2">
                    {
                      this.state.attackMessages.length > 0 ? this.displayAttackMessages() : null
                    }
                  </div>
                </div>
              </Col>
            </div>
          </Tab>
          <Tab eventKey="advanced" title="Advanced Options" disabled={this.props.attackAutomationIsRunning || this.state.isDead}>
            <div className="mt-4">
              <h4>Advanced options</h4>
              <hr />
              <div className="form-check mb-3">
                <input type="checkbox" className="form-check-input" id="manage-skills" onChange={this.showSkillChangeSection.bind(this)}/>
                <label className="form-check-label" htmlFor="manage-skills">Change Active Training Skill?</label>
                <small id="manage-skills-help" className="form-text text-muted">
                  Should you enable this, you are saying you want to train a different skill during this auto fight.
                  Make sure to change it back, when you are done, should you wish to.
                </small>
              </div>
              <div className={this.state.showSkillSection ? '' : 'hide'}>
                <div className="form-group mt-3">
                  <label htmlFor="skill-selection">Select Skill</label>
                  <select className="form-control" id="skill-selection" name="skill-selection"
                          value={this.state.params.skill_id}
                          onChange={this.updateSelectedSkill.bind(this)}
                          disabled={this.disabledInput()}>
                    <option value="0" key="-1">Please select a skill</option>
                    {this.skillOptions()}
                  </select>
                </div>
                <div className="form-group">
                  <label htmlFor="skill-xp-sacrifice">Select XP Sacrificial %</label>
                  <select className="form-control"
                          id="skill-xp-sacrifice"
                          name="skill-xp-sacrifice"
                          value={this.state.params.xp_towards}
                          onChange={this.updateSelectedXPForSkill.bind(this)}
                          disabled={this.disabledInput()}
                  >
                    <option value="0" key="-1">Please select a percentage</option>
                    <option value={0.10} key="0.10">10%</option>
                    <option value={0.20} key="0.20">20%</option>
                    <option value={0.30} key="0.30">30%</option>
                    <option value={0.40} key="0.40">40%</option>
                    <option value={0.50} key="0.50">50%</option>
                    <option value={0.60} key="0.60">60%</option>
                    <option value={0.70} key="0.70">70%</option>
                    <option value={0.80} key="0.80">80%</option>
                    <option value={0.90} key="0.90">90%</option>
                    <option value={1.00} key="1.00">100%</option>
                  </select>
                </div>
                <hr />
              </div>
              <div className="form-group">
                <AlertWarning icon={'fas fa-exclamation-triangle'} title={'Attn!'}>
                  <p>
                    Should you choose the 8 hour mark, you will not be able to start another exploration session after the full
                    eight hours until the next real world day at 12 pm GMT-7. <strong>You can log out and this will continue to run.</strong>
                  </p>
                  <p>The eight hour limit only applies if your auto attack is 8 full complete uninterrupted hours. If you do 2 here, 4 there and 6 over here
                  that will not count towards your total, it must be 8 continuous uninterrupted hours.</p>
                </AlertWarning>
                <label htmlFor="how-long">Select Length of Auto Attack</label>
                <select className="form-control"
                        id="how-long"
                        name="how-long"
                        value={this.state.params.auto_attack_length}
                        onChange={this.updateAutoAttackLength.bind(this)}
                        disabled={this.disabledInput()}>
                  <option value="0" key="-1">Please select a length</option>
                  <option value="1" key="1">1 hour</option>
                  <option value="2" key="2">2 hours</option>
                  <option value="4" key="4">4 hours</option>
                  <option value="6" key="6">6 hours</option>
                  <option value="8" key="8">8 hours</option>
                </select>
              </div>
              <div className="form-check">
                <input type="checkbox" className="form-check-input" id="move-down-list" onChange={this.showMoveDownTheList.bind(this)}/>
                <label className="form-check-label" htmlFor="move-down-list">Enable moving down the list.</label>
                <small id="manage-skills-help" className="form-text text-muted">
                  Should you enable this, you can select how many levels before we attempt to move to the next monster.
                </small>
              </div>
              <div className={this.state.showMoveDownTheList ? '' : 'hide'}>
                <hr />
                <div className="form-group">
                  <AlertWarning icon={'fas fa-exclamation-triangle'} title={'Attn!'}>
                    <p>
                      Should you choose to move down the list and a monster kills you, the exploration will stop.
                      New players are suggested to pick higher values to give more time between leveling.
                    </p>
                    <p>
                      If you have selected the final or close to the final monster in the list and we cannot
                      move any further, we will just stay where we are.
                    </p>
                  </AlertWarning>
                  <label htmlFor="move-down-list">Move down the list every</label>
                  <select className="form-control"
                          id="move-down-list"
                          name="move-down-list"
                          value={this.state.params.move_down_list_every}
                          onChange={this.updateMoveDownListEvery.bind(this)}
                          disabled={this.disabledInput()}
                  >
                    <option value="0" key="-1">Please select a monster</option>
                    <option value={1} key={1}>1 level</option>
                    <option value={5} key={5}>5 levels</option>
                    <option value={10} key={10}>10 levels</option>
                    <option value={15} key={15}>15 levels</option>
                    <option value={20} key={20}>20 levels</option>
                  </select>
                </div>
              </div>
            </div>
          </Tab>
          <Tab eventKey="help" title="Help">
            <div className="mt-4">
              <div className="tw-overflow-y-auto tw-h-60">
                <p>
                  Exploration is similar to <a href="/information/adventure">Adventures</a>, with the exception that they
                  do not lock you out of doing specific actions, but <strong>do let you log out</strong>.
                </p>
                <p>
                  while on a mission you can:
                </p>
                <ul>
                  <li>Move, but not teleport, set sail, traverse or use /PCT to move to and engage with celestials.</li>
                  <li>Manage your kingdoms, but not: wage war or use items on other kingdoms</li>
                  <li>Manage equipment, to an extent. You cannot equip items, but you can move items to other sets, destroy and disenchant items.</li>
                  <li>Manage your Passive skills, Craft, Enchant but not: Switch which skill is in training.</li>
                </ul>
                <p>You cannot use the shop or visit the market. You cannot enter special locations while you are exploring.</p>
                <p>
                  Exploration works differently from adventures such that every 10 minutes your character will do 3 actions, all of them give you XP.
                </p>
                <p>While exploring, every ten minutes your character will:</p>
                <ul>
                  <li>Explore the area around them and engage in an encounter.</li>
                  <li>Fight the creature you selected.</li>
                  <li>Plunder fights the selected monster 1-6 times.</li>
                </ul>
                <p>
                  At the end of the "encounter" we will reward bonus XP and Faction points:
                </p>
                <ul>
                  <li>
                    +200 XP (2 levels)
                  </li>
                  <li>
                    +5 Faction Points (if you have a quest item you will get 50 if you are above level 0. Else you will get 5)
                  </li>
                  <li>
                    +10,000 Gold
                  </li>
                </ul>
                <p>
                  Once Exploration timer is done we give you a total bonus reward:
                </p>
                <ul>
                  <li>
                    +1000 XP (10 levels)
                  </li>
                  <li>
                    +100 FactionPoints (if you have a quest item you will get 1000 if you are above level 0. Else you will get 100)
                  </li>
                  <li>
                    + 100,000 Gold
                  </li>
                </ul>
                <p>Should you die, this will end and we will show you, where the timer is, the result of the last battle message so you can see why you died. If you are logged out, you will not be able to
                know what killed you as the Event Log tab below works like server messages, they are not saved.</p>
                <p>Should you choose to do the 8 hour mark, you will be locked out of explorations till the next real world day at 12pm GMT -7</p>
                <p>You can do Exploration in bits and pieces, such an hour here and a 4 over there and 6 an hour later, <strong>this will not count towards the 8 hour mark.</strong></p>
                <p>Each action will spit out to the Event Logs tab down in the chat section, we will not show you the results of battles unless you die.</p>
                <p>Finally, all rewards listed above will be given to you automatically and we do respect auto disenchanting <a href="/information/settings">settings</a>.</p>
              </div>
            </div>
          </Tab>
        </Tabs>
      </div>
    )
  }
}
