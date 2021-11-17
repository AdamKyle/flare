import React from 'react';
import {Col, Tab, Tabs} from "react-bootstrap";
import AlertWarning from "../components/base/alert-warning";
import AlertInfo from "../components/base/alert-info";
import TimeOutBar from "../timeout/timeout-bar";
import AlertError from "../components/base/alert-error";

export default class AutoAttackSection extends React.Component {

  constructor(props) {
    super(props);

    const skillInTraining = this.props.character.skills.filter((skill) => skill.is_training);

    this.state = {
      character: this.props.character,
      monsters: this.props.monsters,
      isDead: this.props.character.is_dead,
      errorMessage: null,
      isLoading: false,
      timeRemaining: null,
      showSkillSection: false,
      showMoveDownTheList: false,
      trainableSkills: this.props.character.skills.filter((skill) => skill.can_train),
      params: {
        skill_id: skillInTraining.length > 0 ? skillInTraining[0].id : null,
        xp_towards: skillInTraining.length > 0 ? skillInTraining[0].xp_towards : null,
        auto_attack_length: null,
        move_down_the_list_every: null,
        selected_monster_id: null,
      }
    }
  }

  updateSelectedMonster(event) {
    const params = _.cloneDeep(this.state.params);

    params.selected_monster_id = parseInt(event.target.value) || 0;

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

    params.move_down_the_list_every = parseInt(event.target.value) || 0;

    this.setState({
      params: params,
    });
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

  beginFight() {

    if (this.state.params.selected_monster_id === null) {
      this.setState({
        errorMessage: 'You must select a monster'
      });

      return;
    }

    if (this.state.params.auto_attack_length === null) {
      this.setState({
        errorMessage: 'How long should this auto attack go for? Check Advanced tab and configure a length.'
      });

      return;
    }

    this.setState({
      errorMessage: null,
      isLoading: true,
    }, () => {
      console.log('ajax here ...');
    });
  }

  render() {
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
                <div className="form-group">
                  <label htmlFor="monsters-auto-attack">Select Monster</label>
                  <select className="form-control monster-select" id="monsters-auto-attack" name="monsters-auto-attack"
                          value={this.state.params.selected_monster_id}
                          onChange={this.updateSelectedMonster.bind(this)}
                          disabled={this.disabledInput()}>
                    <option value="0" key="-1">Please select a monster</option>
                    {this.monsterOptions()}
                  </select>
                </div>
                <button className="btn btn-primary mt-3" onClick={this.beginFight.bind(this)} disabled={this.state.isLoading || this.disabledInput()}>
                  {this.state.isLoading ? <i className="fas fa-spinner fa-spin"></i> : null} Begin!
                </button>
              </Col>

              <Col lg={12} xl={6}>
                <div className="tw-text-center">
                  <TimeOutBar
                    cssClass={'character-timeout'}
                    readyCssClass={'character-ready'}
                    timeRemaining={this.state.timeRemaining}
                    channel={'show-timeout-bar-' + this.props.userId}
                    eventClass={'Game.Core.Events.ShowTimeOutEvent'}
                  />
                  <div className="tw-mt-2">
                    Attack Output.
                  </div>
                </div>
              </Col>
            </div>
          </Tab>
          <Tab eventKey="advanced" title="Advanced Options">
            <div className="mt-4">
              <h4>Advanced options</h4>
              <hr />
              <div className="form-check">
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
                    Should you choose the 8 hour mark, you will not be able to start another auto attack session after the full
                    eight hours. You will need to refresh your screen every 45 minutes or so, so your session does not die.
                    Most players will play with the character screen in one tab and the game in the other, allowing them to refresh the character sheet
                    and not loose chat history in the game tab. You could do the same, and just refresh the character tab every so often.
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
                      Should you choose to move down the list and a monster kills you, the auto battle will stop.
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
                    <option>1 level</option>
                    <option>5 levels</option>
                    <option>10 levels</option>
                    <option>15 levels</option>
                    <option>20 levels</option>
                  </select>
                </div>
              </div>
            </div>
          </Tab>
          <Tab eventKey="help" title="Help">
            <div className="mt-4">
              <div className="tw-overflow-y-auto tw-h-60">
                <AlertWarning icon={'fas fa-exclamation-triangle'} title={"ATTN!"}>
                  <p>If you log out or your session dies, <strong>this will stop</strong>. If you die, <strong>this will stop</strong>.</p>
                  <p>Once you reach a total of 8 hours, at once (see below about refreshing) <strong>This will stop</strong> AND <strong>You wont be able
                  to initiate another auto battle for the rest of the day.</strong></p>
                </AlertWarning>
                <p className="tw-text-red-700">
                  This feature was designed for players who want to play with this game in a separate tab and check on it every once in a while. Not for
                  players who want to set it and forget it. That is not Tlessa!
                </p>
                <h3>What can I do while this is running?</h3>
                <p>
                  While the auto attack runs, you can still craft, enchant, craft alchemical items, move around (but not traverse, teleport or set sail) and manage your kingdoms.
                  You will not be able to traverse, go on adventures or take part in Celestial fights, including being able to instantly teleport to one.
                  You will also not be able to complete quests or interact with NPC's. Quest drops are fine, but the interacting with NPC's,
                  they will tell you "you are too busy". You will also not be able to use any items be it on your self or kingdoms and you cannot wage war.
                </p>
                <p>You also cannot manage your character in terms of equipping, changing sets or manging training based skills.</p>
                <p><em>Don't look at me like that child, you can't be able to wage a war and fight a beast at the same time. You need to focus on one thing at a time.</em></p>
                <h3>Why can't I just leave this running for ever?</h3>
                <p>
                  Planes of Tlessa is an active game. To keep you coming back, we limit the time frame this feature can run in.
                  Your session also expires after 90 minutes of inactivity. Most players will come back before the session expires
                  to refresh the page.
                </p>
                <h3>So I can just refresh every 85 minutes or so and go for ever?</h3>
                <p>
                  Yes and no. If the auto attack has run for longer then 8 hours<sup>*</sup> it will cut out and you wont be able to
                  set up auto attack for the rest of the day. Tlessa wants you engaged with the game and the community, this is just an
                  additional part to take some of the strain of leveling out.
                </p>
                <p><sup>*</sup> That's 8 <strong>full uninterrupted hours</strong>. Doing it in pieces, 2 hours here, 4 hours there and 6 hours an hour later will <strong>NOT</strong>
                count towards the 8 hour limit.</p>
                <AlertInfo icon={'fas fa-question-circle'} title={"ATTN!"}>
                  <p>Tlessa was never intended to be an idle game, and while idle features do make their way in, they come with limitations.</p>
                  <p>The auto attack is the only automatic feature that will stop you from being able to set it up again after the max time has passed.</p>
                </AlertInfo>
              </div>
            </div>
          </Tab>
        </Tabs>
      </div>
    )
  }
}