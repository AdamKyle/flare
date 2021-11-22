import React from 'react';
import {Col, Row, Tab, Tabs} from 'react-bootstrap';
import CharacterDetails from "./sheet/character-details";
import Boons from "./boons";
import InventoryDetails from "./sheet/Inventory-details";
import SkillDetails from "./sheet/skill-details";
import Automations from "./automations";
import Factions from "./factions";

export default class CharacterSheet extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      characterSheet: {},
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
        }, () => {
          if (result.data.sheet.timeout_until !== null) {
            this.props.openTimeOutModal(result.data.sheet.timeout_until)
          }
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
    const automations = this.state.characterSheet.automations;

    if (automations.length > 0) {
      return <span className="tw-text-green-600">
        <i className="fas fa-cog fa-spin"></i> Current Automations
      </span>
    }

    return <span>Current Automations</span>
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
                  <div className="alert alert-warning mt-2 mb-3">
                    You are dead. You will not be able to manage: Inventory, Skills or Boons. Please revive.
                    You can revive by heading to the game section and clicking revive.
                    Remember: <strong>Dead people cannot do things.</strong>
                  </div>
                : null
              }

              {
                !this.state.characterSheet.can_adventure ?
                  <div className="alert alert-warning mt-2 mb-3">
                    You are currently adventuring. You will not be able to manage: Inventory, Skills or Boons.
                    You can cancel an adventure by heading to the game section and clicking adventure on the map
                    and then click cancel for the current running adventure.
                  </div>
                : null
              }

              <Row>
                <Col lg={12} xl={6}>
                  <Tabs defaultActiveKey="character-info" id="character-section">
                    <Tab eventKey="character-info" title="Character Info">
                      <CharacterDetails
                        characterSheet={this.state.characterSheet}
                      />
                    </Tab>
                    <Tab eventKey="character-boons" title="Active Boons">
                      <Boons characterId={this.props.characterId} userId={this.props.userId} />
                    </Tab>
                    <Tab eventKey="character-automation" title={this.automationsTitle()}>
                      <Automations automations={this.state.characterSheet.automations} />
                    </Tab>
                    <Tab eventKey="character-faction" title='Factions'>
                      <Factions factions={this.state.characterSheet.factions} />
                    </Tab>
                  </Tabs>
                  <SkillDetails
                    skills={this.state.characterSheet.skills}
                    characterId={this.props.characterId}
                    canAdventure={this.state.characterSheet.can_adventure}
                    isDead={this.state.characterSheet.is_dead}
                  />
                </Col>
                <Col lg={12} xl={6}>
                  <InventoryDetails
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