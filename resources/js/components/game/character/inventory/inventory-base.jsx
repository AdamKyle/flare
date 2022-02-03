import React, {Fragment} from 'react';
import {Tabs, Tab} from 'react-bootstrap';
import InventorySection from "./sections/inventory-section";
import EquippedSection from "./sections/equipped-section";
import SetsSection from "./sections/sets-section";
import QuestSection from "./sections/quest-section";
import UsableItemsSection from "./sections/usable-items-section";
import AlertInfo from "../../components/base/alert-info";
import AlertWarning from "../../components/base/alert-warning";

export default class InventoryBase extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      inventory: {},
    }

    this.updateInventory = Echo.private('update-inventory-' + this.props.userId);
  }

  getSlotId(itemId) {
    const foundSlot = this.state.inventory.inventory.filter((i) => i.item.id === itemId);

    if (foundSlot.length > 0) {
      return foundSlot[0].id
    }

    return null;
  }

  componentDidMount() {
    axios.get('/api/character/'+this.props.characterId+'/inventory')
      .then((result) => {
        this.setState({
          loading: false,
          inventory: result.data,
        });
      }).catch((error) => {
        if (error.hasOwnProperty('response')) {
          const response = error.response;

          if (response.status === 401) {
            return location.reload()
          }

          if (response.status === 429) {
            return window.location.replace('/game');
          }
        }
      });

    this.updateInventory.listen('Game.Core.Events.CharacterInventoryUpdateBroadCastEvent', (event) => {
      this.setState({
        inventory: event.inventory,
      });
    });
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
            <Fragment>
              {/*{*/}
              {/*  this.props.automations.length > 0 ?*/}
              {/*    <AlertWarning icon={'fas fa-exclamation-triangle'} title={'Automation is running'}>*/}
              {/*      <p>*/}
              {/*        You cannot equip items while you have automation running. You cannot equip sets, or manage currently*/}
              {/*        equipped items. How ever, you can move items between sets, destroy and disenchant items.*/}
              {/*        For example, if you are auto battling and get a Faction reward item, you CAN move it to a set, but you CANNOT equip it.*/}
              {/*      </p>*/}
              {/*    </AlertWarning>*/}
              {/*  : null*/}
              {/*}*/}

              <Tabs defaultActiveKey="inventory" id="inventory-section">
                <Tab eventKey="inventory" title="Inventory">
                  <InventorySection
                    characterId={this.props.characterId}
                    inventory={this.state.inventory.inventory}
                    usableSets={this.state.inventory.usable_sets}
                    getSlotId={this.getSlotId.bind(this)}
                  />
                </Tab>
                <Tab eventKey="equipped" title="Equipped">
                  <EquippedSection
                    characterId={this.props.characterId}
                    equipped={this.state.inventory.equipped}
                    usableSets={this.state.inventory.savable_sets}
                  />
                </Tab>
                <Tab eventKey="sets" title="Sets">
                  <SetsSection
                    characterId={this.props.characterId}
                    sets={this.state.inventory.sets}
                  />
                </Tab>
                <Tab eventKey="usable-items" title="Usable Items">
                  <UsableItemsSection
                    characterId={this.props.characterId}
                    usableItems={this.state.inventory.usable_items}
                  />
                </Tab>
                <Tab eventKey="quest-items" title="Quest Items">
                  <QuestSection questItems={this.state.inventory.quest_items} />
                </Tab>
              </Tabs>
            </Fragment>
        }
      </>
    )
  }
}
