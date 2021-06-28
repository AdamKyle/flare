import React from 'react';
import Card from "../components/templates/card";
import {Col, Row} from "react-bootstrap";
import ConjureModal from "./modals/conjure-modal";

export default class ConjureComponent extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      celestialMonsters: [],
      openConjureModal: false,
      type: null,
      celestialId: 0,
      selectedCelestial: null,
      gold: 0,
      goldDust: 0,
    }
  }

  componentDidMount() {
    axios.get('/api/celestial-beings/' + this.props.characterId).then((result) => {
      this.setState({
        celestialMonsters: result.data.celestial_monsters,
        gold: result.data.character_gold,
        goldDust: result.data.character_gold_dust,
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.data === 429) {
          return this.props.openTimeOutModal();
        }
      }
    });
  }

  celestialOptions() {
    return this.state.celestialMonsters.map((cm) => {
      return <option value={cm.id} key={cm.id}>{cm.name} -
        Gold: {cm.gold_cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}, Gold
        Dust: {cm.gold_dust_cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</option>
    });
  }

  selectCelestial(e) {
    this.setState({
      celestialId: parseInt(event.target.value),
    });
  }

  conjure(type) {
    this.setState({
      openConjureModal: true,
      type: type,
      selectedCelestial: this.state.celestialMonsters.filter((cm) => cm.id === this.state.celestialId)[0],
    });
  }

  closeConjureModal() {
    this.setState({
      openConjureModal: false,
    });
  }

  isDisabled() {
    return this.props.isDead;
  }

  render() {
    return (
      <Card close={this.props.closeComponent} cardTitle="Conjure (NPC choice)">
        <div className="alert alert-info">
          <p>
            Below you may select which Celestial Entity you wish to have this npc conjure. <br/>
            Celestial Entities are known for dropping Crystal Shards. Crystal Shards are used in Alchemy to craft
            powerful weapons for attacking kingdoms.
          </p>
          <p>To Conjure you must have both Gold <strong>and</strong> Gold Dust. Gold Dust is earned by you Disenchanting
            items or destroying them. Once you meet the requirements of the
            creature in the list cost wise, you can click "Conjure" and a global message will go out stating that you
            have conjured a Celestial Entity. These creatures spawn at random locations
            and if that location is a kingdom it has a specific percentage chance of dealing damage to the whole kingdom
            (including morale), which in it's self is a % of damage done.</p>
          <p>You must go to the location the Celestial Entity spawns at, to fight it. These fights are done server side
            with a 5 second timeout in between attacks, to allow other players a chance
            to attack.</p>
          <p>The first one to kill the Celestial Entity gets the reward (which will be seen as a both server and global
            message). There is an option to privately summon the Celestial Entity at twice the cost of the public
            summon.</p>
          <p>Celestial Entities are also <strong>much harder</strong> then regular critters, and have a
            one-out-of-a-million chance to spawn naturally by your character moving around the map (in all forms of
            movement).
            Players moving on one plane can cause a Celestial Entity to appear on another plane.</p>
          <p><strong>Conjuring Celestial Entities Can be done from any plane, for that plane - be it public or
            private.</strong></p>
        </div>

        {
          this.state.loading ?
            <div className="progress mb-2 mt-2" style={{position: 'relative', height: '5px'}}>
              <div className="progress-bar progress-bar-striped indeterminate">
              </div>
            </div>
            :
            <>
              <Row>
                <Col xs={12} sm={12} md={6} lg={6} xl={6}>
                  <select className="form-control monster-select" id="monsters" name="monsters"
                          value={this.state.celestialId}
                          onChange={this.selectCelestial.bind(this)}>
                    <option value="" key="0">Please select a monster</option>
                    {this.celestialOptions()}
                  </select>
                </Col>
                <Col xs={12} sm={12} md={6} lg={6} xl={6}>
                  <button className="btn btn-primary"
                          type="button"
                          disabled={this.isDisabled()}
                          onClick={() => this.conjure('private')}
                  >
                    Privately Conjure
                  </button>
                  <button className="btn btn-primary ml-3"
                          type="button"
                          disabled={this.isDisabled()}
                          onClick={() => this.conjure('public')}
                  >
                    Publicly Conjure!
                  </button>
                </Col>
              </Row>

              {
                this.state.openConjureModal ?
                  <ConjureModal
                    characterId={this.props.characterId}
                    show={this.state.openConjureModal}
                    close={this.closeConjureModal.bind(this)}
                    closeComponent={this.props.closeComponent}
                    type={this.state.type}
                    celestial={this.state.selectedCelestial}
                    gold={this.state.gold}
                    goldDust={this.state.goldDust}
                    openTimeOutModal={this.props.openTimeOutModal}
                  />
                  : null
              }
            </>
        }

      </Card>
    )
  }
}

