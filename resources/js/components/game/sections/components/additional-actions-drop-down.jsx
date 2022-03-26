import React from 'react';
import {Dropdown} from 'react-bootstrap';
import {getServerMessage} from '../../helpers/server_message';
import LockedLocationType from "../lib/LockedLocationType";

export default class AdditionalActionsDropDown extends React.Component {

  constructor(props) {
    super(props)

    this.state = {
      showCrafting: false,
      showEnchanting: false,
      showAlchemy: false,
      showSmithingBench: false,
      showTrinketCrafting: false,
    }
  }

  addCraftingAction() {
    if (!this.props.canCraft) {
      return getServerMessage('cant_craft');
    }

    this.setState({
      showCrafting: !this.state.showCrafting,
      showEnchanting: false,
      showAlchemy: false,
      showSmithingBench: false,
      showTrinketCrafting: false,
    }, () => {
      this.props.updateShowCrafting(this.state.showCrafting);
      this.props.updateShowEnchanting(this.state.showEnchanting);
      this.props.updateShowAlchemy(this.state.showAlchemy);
      this.props.updateShowSmithingBench(this.state.showSmithingBench);
      this.props.updateShowTrinketCrafting(this.state.showTrinketCrafting);
    });
  }

  addEnchantingAction() {
    if (!this.props.canCraft) {
      return getServerMessage('cant_enchant');
    }

    this.setState({
      showEnchanting: !this.state.showEnchanting,
      showCrafting: false,
      showAlchemy: false,
      showSmithingBench: false,
      showTrinketCrafting: false,
    }, () => {
      this.props.updateShowCrafting(this.state.showCrafting);
      this.props.updateShowEnchanting(this.state.showEnchanting);
      this.props.updateShowAlchemy(this.state.showAlchemy);
      this.props.updateShowSmithingBench(this.state.showSmithingBench);
      this.props.updateShowTrinketCrafting(this.state.showTrinketCrafting);
    });
  }

  addAlchemySection() {
    if (!this.props.canCraft) {
      return getServerMessage('cant_enchant');
    }

    this.setState({
      showEnchanting: false,
      showCrafting: false,
      showAlchemy: !this.state.showAlchemy,
      showSmithingBench: false,
      showTrinketCrafting: false,
    }, () => {
      this.props.updateShowCrafting(this.state.showCrafting);
      this.props.updateShowEnchanting(this.state.showEnchanting);
      this.props.updateShowAlchemy(this.state.showAlchemy);
      this.props.updateShowSmithingBench(this.state.showSmithingBench);
      this.props.updateShowTrinketCrafting(this.state.showTrinketCrafting);
    });
  }

  addPurgatorySmithSection() {
    if (!this.props.canCraft) {
      return getServerMessage('cant_craft');
    }

    this.setState({
      showEnchanting: false,
      showCrafting: false,
      showAlchemy: false,
      showTrinketCrafting: false,
      showSmithingBench: !this.state.showSmithingBench
    }, () => {
      this.props.updateShowCrafting(this.state.showCrafting);
      this.props.updateShowEnchanting(this.state.showEnchanting);
      this.props.updateShowAlchemy(this.state.showAlchemy);
      this.props.updateShowSmithingBench(this.state.showSmithingBench);
      this.props.updateShowTrinketCrafting(this.state.showTrinketCrafting);
    });
  }

  addTrinketCraftingAction() {
    if (!this.props.canCraft) {
      return getServerMessage('cant_craft');
    }

    this.setState({
      showEnchanting: false,
      showCrafting: false,
      showAlchemy: false,
      showSmithingBench: false,
      showTrinketCrafting: !this.state.showTrinketCrafting,
    }, () => {
      this.props.updateShowCrafting(this.state.showCrafting);
      this.props.updateShowEnchanting(this.state.showEnchanting);
      this.props.updateShowAlchemy(this.state.showAlchemy);
      this.props.updateShowSmithingBench(this.state.showSmithingBench);
      this.props.updateShowTrinketCrafting(this.state.showTrinketCrafting);
    });
  }

  changeType() {
    if (!this.props.canCraft) {
      return getServerMessage('cant_craft');
    }

    this.props.changeCraftingType(true);
  }

  render() {
    return (
      <div className="mb-2">
        <Dropdown>
          <Dropdown.Toggle variant="primary" id="dropdown-basic" size="sm"
                           disabled={this.props.isDead || this.props.isAdventuring}>
            Craft/Enchant
          </Dropdown.Toggle>

          <Dropdown.Menu>
            <Dropdown.Item
              onClick={this.addCraftingAction.bind(this)}>{this.state.showCrafting ? 'Remove Crafting' : 'Craft'}</Dropdown.Item>
            <Dropdown.Item
              onClick={this.addEnchantingAction.bind(this)}>{this.state.showEnchanting ? 'Remove Enchanting' : 'Enchant'}</Dropdown.Item>
            <Dropdown.Item
              onClick={this.addTrinketCraftingAction.bind(this)}>{this.state.showTrinketCrafting ? 'Remove Trinket Crafting' : 'Trinket Crafting'}</Dropdown.Item>
            {
              !this.props.isAlchemyLocked ?
                <Dropdown.Item
                  onClick={this.addAlchemySection.bind(this)}>{this.state.showAlchemy ? 'Remove Alchemy' : 'Alchemy'}</Dropdown.Item>
              : null
            }
            {
              this.props.lockedLocationType === LockedLocationType.PURGATORYSMITHSHOUSE ?
                <Dropdown.Item
                  onClick={this.addPurgatorySmithSection.bind(this)}>{this.state.showSmithingBench ? 'Remove Work Bench' : 'Smiths Work Bench'}</Dropdown.Item>
              : null
            }

            {this.state.showCrafting
              ?
              <Dropdown.Item onClick={this.changeType.bind(this)}>Change Crafting Type</Dropdown.Item>
              : null
            }
          </Dropdown.Menu>
        </Dropdown>
      </div>
    );
  }
}
