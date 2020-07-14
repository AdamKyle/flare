import React from 'react';
import { Dropdown } from 'react-bootstrap';
import { getServerMessage } from '../helpers/server_message';

export default class AdditionalCoreActionsDropDown extends React.Component {

    constructor(props) {
      super(props)
      
      this.state = {
        showCrafting: false,
      }
    }

    addCraftingAction() {
      if (!this.props.canCraft) {
        return getServerMessage('cant_craft');
      }

      this.setState({
        showCrafting: this.state.showCrafting ? false : true,
      }, () => {
        this.props.updateShowCrafting(this.state.showCrafting);
      });
    }

    changeType() {
      if (!this.props.canCraft) {
        return getServerMessage('cant_craft');
      }

      this.props.changeCraftingType(true);
    }

    render() {
      return(
          <Dropdown>
            <Dropdown.Toggle variant="primary" id="dropdown-basic" size="sm" disabled={this.props.isDead || this.props.isAdventuring}>
              Additional actions
            </Dropdown.Toggle>

            <Dropdown.Menu>
            <Dropdown.Item onClick={this.addCraftingAction.bind(this)}>{this.state.showCrafting ? 'Remove Crafting' : 'Craft'}</Dropdown.Item>
            {this.state.showCrafting
              ?
              <Dropdown.Item onClick={this.changeType.bind(this)}>Change Type</Dropdown.Item>
              : null
            }
            </Dropdown.Menu>
          </Dropdown>
      );
    }
}