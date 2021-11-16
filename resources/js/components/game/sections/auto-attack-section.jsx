import React from 'react';
import {Col, Tab, Tabs} from "react-bootstrap";
import AlertWarning from "../components/base/alert-warning";
import AlertInfo from "../components/base/alert-info";
import TimeOutBar from "../timeout/timeout-bar";

export default class AutoAttackSection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      character: this.props.character,
      monsters: this.props.monsters,
      isDead: this.props.character.is_dead,
      selectedMonster: 0,
      timeRemaining: null,
    }
  }

  componentDidMount() {
  }

  updateSelectedMonster(event) {

    this.setState({
      selectedMonster: parseInt(event.target.value),
    });
  }

  monsterSelectDisabled() {
    return false;
  }

  monsterOptions() {
    return this.state.monsters.map((monster) => {
      return <option value={monster.id} key={monster.id}>{monster.name}</option>
    });
  }

  render() {
    return (
      <div className="mt-4">
        <Tabs defaultActiveKey="general" id="auto-general-config-tab-section">
          <Tab eventKey="general" title="General">
            <div className="row mt-4">
              <Col lg={12} xl={6}>
                <div className="form-group">
                  <label htmlFor="monsters-auto-attack">Select Monster</label>
                  <select className="form-control monster-select" id="monsters-auto-attack" name="monsters-auto-attack"
                          value={this.state.selectedMonster}
                          onChange={this.updateSelectedMonster.bind(this)}
                          disabled={this.monsterSelectDisabled()}>
                    <option value="0" key="-1">Please select a monster</option>
                    {this.monsterOptions()}
                  </select>
                </div>
                <div className="btn btn-primary mt-3">Begin!</div>
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
                <input type="checkbox" className="form-check-input" id="manage-skills" />
                <label className="form-check-label" htmlFor="manage-skills">Change Active Training Skill?</label>
                <small id="manage-skills-help" className="form-text text-muted">
                  Should you enable this, you are saying you want to train a different skill during this auto fight.
                  Make sure to change it back, when you are done, should you wish to.
                </small>
              </div>
              <div className="form-group mt-3">
                <label htmlFor="exampleFormControlSelect1">Select Skill</label>
                <select className="form-control" id="exampleFormControlSelect1">
                  <option>1</option>
                  <option>2</option>
                  <option>3</option>
                  <option>4</option>
                  <option>5</option>
                </select>
              </div>
              <div className="form-group">
                <label htmlFor="exampleFormControlSelect2">Select XP Sacrificial %</label>
                <select className="form-control" id="exampleFormControlSelect2">
                  <option>10%</option>
                  <option>20%</option>
                  <option>30%</option>
                  <option>40%</option>
                  <option>50%</option>
                  <option>60%</option>
                  <option>70%</option>
                  <option>80%</option>
                  <option>90%</option>
                  <option>100%</option>
                </select>
              </div>
              <hr />
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
                <label htmlFor="exampleFormControlSelect3">Select Length of Auto Attack</label>
                <select className="form-control" id="exampleFormControlSelect3">
                  <option>1 hour</option>
                  <option>2 hours</option>
                  <option>4 hours</option>
                  <option>6 hours</option>
                  <option>8 hours</option>
                </select>
              </div>
              <div className="form-check">
                <input type="checkbox" className="form-check-input" id="manage-skills" />
                <label className="form-check-label" htmlFor="manage-skills">Enable moving down the list.</label>
                <small id="manage-skills-help" className="form-text text-muted">
                  Should you enable this, you can select how many leveles before we attempt to move to the next monster.
                </small>
              </div>
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
                <label htmlFor="exampleFormControlSelect3">Move down the list every</label>
                <select className="form-control" id="exampleFormControlSelect3">
                  <option>1 level</option>
                  <option>5 levels</option>
                  <option>10 levels</option>
                  <option>15 levels</option>
                  <option>20 levels</option>
                </select>
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
                  Your session also expires after 60 minutes of inactivity. Most players will come back before the session expires
                  to refresh the page.
                </p>
                <h3>So I can just refresh every 45 minutes or so and go for ever?</h3>
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