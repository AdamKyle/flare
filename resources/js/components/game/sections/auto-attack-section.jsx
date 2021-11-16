import React from 'react';
import {Col, Tab, Tabs} from "react-bootstrap";
import AlertWarning from "../components/base/alert-warning";
import AlertInfo from "../components/base/alert-info";

export default class AutoAttackSection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      character: this.props.character,
      monsters: this.props.monsters,
      isDead: this.props.character.is_dead,
    }
  }

  componentDidMount() {
  }

  buildMonsterList() {

  }

  render() {
    return (
      <div className="mt-4">
        <Tabs defaultActiveKey="general" id="auto-general-config-tab-section">
          <Tab eventKey="general" title="General">
            <div className="row mt-4">
              <Col lg={12} xl={6}>
                <div className="form-group">
                  <label htmlFor="exampleFormControlSelect1">Select Monster</label>
                  <select className="form-control" id="exampleFormControlSelect1">
                    <option>1</option>
                    <option>2</option>
                    <option>3</option>
                    <option>4</option>
                    <option>5</option>
                  </select>
                </div>
                <div className="btn btn-primary mt-3">Begin!</div>
              </Col>

              <Col lg={12} xl={6}>
                Attack Output.
              </Col>
            </div>
          </Tab>
          <Tab eventKey="advanced" title="Advanced Options">
            <div className="mt-4">
              Advanced options.
              <ul>
                <li>
                  See current skill in training and adjust if need be.
                </li>
                <li>
                  Select how many times to fight a single monster Max is 1000 times.
                </li>
              </ul>
            </div>
          </Tab>
          <Tab eventKey="help" title="Help">
            <div className="mt-4">
              <div className="tw-overflow-y-auto tw-h-60">
                <AlertWarning icon={'fas fa-exclamation-triangle'} title={"ATTN!"}>
                  <p>If you log out or your session dies, <strong>this will stop</strong>. If you die, <strong>this will stop</strong>.</p>
                  <p>Once you reach a total of 8 hours, at once (see below about refreshing) <strong>This will stop</strong> AND <strong>You wont be able
                  to innate another auto battle for the rest of the day.</strong></p>
                </AlertWarning>
                <p className="tw-text-red-700">
                  This feature was designed for players who want to play with this game in a separate tab and check on it every once in a while. Not for
                  players who want to set it and forget it. that is not Tlessa!
                </p>
                <h3>What can I do while this is running?</h3>
                <p>
                  While the auto attack runs, you can still craft, enchant, craft alchemical items, move around (but not traverse) and manage your kingdoms.
                  You will not be able to traverse, go on adventures or take part in Celestial fights, including being able to instantly teleport to one.
                  You will also not be able to complete quests or interact with NPC's. Quest drops are fine, but the interacting with NPC's,
                  they will tell you "you are too busy". You will also not be able to use any items be it on your self or kingdoms and you cannot wage war.
                </p>
                <p><em>Don't look at me like that child, you can't be able to wage a war and fight a beast at the same time. You need to focus on one thing at a time.</em></p>
                <h3>Why can't I just leave this running for ever?</h3>
                <p>
                  Planes of Tlessa is an active game. To keep you coming back, we limit the time frame this feature can run in.
                  Your session also expires after 60 minutes of inactivity. Most players will come back before the session expires
                  to refresh the page.
                </p>
                <h3>So I can just refresh every 45 minutes or so and go for ever?</h3>
                <p>
                  Yes and no. If the auto attack has run for longer then 8 hours it will cut out and you wont be able to
                  set up auto attack for the rest of the day. Tlessa wants you engaged with the game and the community, this is just a
                  additional part to take some of the strain of leveling out.
                </p>
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