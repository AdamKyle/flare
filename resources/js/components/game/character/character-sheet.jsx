import React from 'react';
import {Col, Row, Tab, Tabs} from 'react-bootstrap';
import CharacterDetails from "./sheet/character-details";
import Boons from "./boons";
import InventoryDetails from "./sheet/Inventory-details";
import SkillDetails from "./sheet/skill-details";
import Automations from "./automations";
import Factions from "./factions";
import AlertWarning from "../components/base/alert-warning";

export default class CharacterSheet extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      characterSheet: {},
      isAutomationRunning: false,
      loading: true,
    }

    this.sheetUpdate = Echo.private('update-top-bar-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/character-sheet/' + this.props.characterId)
      .then((result) => {
        this.setState({
          characterSheet: result.data.sheet,
          loading: false,
        });
      }).catch((err) => {
        this.setState({loading: false});
        if (err.hasOwnProperty('response')) {
          const response = err.response;

          if (response.status === 401) {
            return location.reload()
          }

          if (response.status === 429) {
            return this.props.openTimeOutModal()
          }
        }
      });

    this.sheetUpdate.listen('Game.Core.Events.UpdateTopBarBroadcastEvent', (event) => {
      this.setState({
        characterSheet: event.characterSheet,
      });
    });
  }

  automationsTitle() {

    if (this.state.isAutomationRunning) {
      return <span className="tw-text-green-600">
        <i className="fas fa-cog fa-spin"></i> Current Automations
      </span>
    }

    return <span>Current Automations</span>
  }

  isAutomationRunning(isRunning) {
    this.setState({
      isAutomationRunning: isRunning,
    })
  }

  render() {
    return (
      <>
        {
          this.state.loading ?
            <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
              <div className="progress-bar progress-bar-striped indeterminate">
              </div>
            </div>
          :
            <>
              {
                this.state.characterSheet.is_dead ?
                  <AlertWarning icon={'fas fa-exclamation-triangle'} title={'You are a bit dead friend ...'}>
                    <p>
                      You are dead. You will not be able to manage: Inventory, Skills or Boons. Please revive.
                      You can revive by heading to the game section and clicking revive.
                      Remember: <strong>Dead people cannot do things.</strong>
                    </p>
                  </AlertWarning>
                : null
              }

              {
                this.state.isAutomationRunning ?
                  <AlertWarning icon={'fas fa-exclamation-triangle'} title={'Automation is running'}>
                    <p>
                      You are currently in the process of automation. As a result, you cannot switch skills to train,
                      equip items or sets - but you can move items around, disenchant and destroy. You cannot sell to the shop,
                      the market board (or buy from either), set sail, teleport, traverse .... <em>Deep breath!</em> ... use /pct chat command
                      (to travel to celestials, and no you cannot engage them either),
                      engage with NPC's, settle kingdoms, Use items on your self (or other kingdoms) or wage war.
                      But you CAN move (You cannot enter special locations while auto battling)
                      and you can manage your own kingdoms. Got it? Good! I can breathe again.
                    </p>
                    <p>
                      Oh and child ... Don't log out or die, or that's it for automation.
                    </p>
                  </AlertWarning>
                  : null
              }

              {
                !this.state.characterSheet.can_adventure ?
                  <AlertWarning icon={'fas fa-exclamation-triangle'} title={'Automation is running'}>
                    You are currently adventuring. You will not be able to manage: Inventory, Skills or Boons.
                    You can cancel an adventure by heading to the game section and clicking adventure on the map
                    and then click cancel for the current running adventure.
                  </AlertWarning>
                : null
              }

              <Row>
                <Col lg={12} xl={6}>
                  <Tabs defaultActiveKey="character-info" id="character-section">
                    <Tab eventKey="character-info" title="Character Info">
                      <CharacterDetails
                        characterId={this.props.characterId}
                        userId={this.props.userId}
                      />
                    </Tab>
                    <Tab eventKey="character-boons" title="Active Boons">
                      <Boons characterId={this.props.characterId} userId={this.props.userId} />
                    </Tab>
                    <Tab eventKey="character-automation" title={this.automationsTitle()}>
                      <Automations characterId={this.props.characterId} userId={this.props.userId} isAutomationRunning={this.isAutomationRunning.bind(this)}/>
                    </Tab>
                    <Tab eventKey="character-faction" title='Factions'>
                      <Factions characterId={this.props.characterId} userId={this.props.userId} canAutoBattle={this.state.characterSheet.can_auto_battle}/>
                    </Tab>
                  </Tabs>
                  <SkillDetails
                    canAutoBattle={!this.state.isAutomationRunning}
                    characterId={this.props.characterId}
                    userId={this.props.userId}
                    canAdventure={this.state.characterSheet.can_adventure}
                    isDead={this.state.characterSheet.is_dead}
                  />
                </Col>
                <Col lg={12} xl={6}>
                  <InventoryDetails
                    isAutomationRunning={this.state.isAutomationRunning}
                    characterSheet={this.state.characterSheet}
                    characterId={this.props.characterId}
                    userId={this.props.userId}
                  />
                </Col>
              </Row>
            </>
        }
      </>
    );
  }
}
