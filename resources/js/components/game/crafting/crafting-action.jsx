import React from 'react';
import {Row, Col} from 'react-bootstrap';
import TimeOutBar from '../timeout/timeout-bar';
import {getServerMessage} from '../helpers/server_message';
import moment from 'moment';

export default class CraftingAction extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      canCraft: true,
      isDead: this.props.isDead,
      itemToCraft: 0,
      itemsToCraft: [],
      craftingType: null,
      showCrafting: true,
      chracterId: this.props.characterId,
      timeRemaining: this.props.timeRemaining,
      showSuccess: false,
      gold: 0,
    }

    this.craftingTimeOut = Echo.private('show-crafting-timeout-bar-' + this.props.userId);
    this.topBar = Echo.private('update-top-bar-' + this.props.userId);
    this.craftingList = Echo.private('update-crafting-list-' + this.props.userId);
  }

  componentDidMount() {
    this.setState({
      showCrafting: this.props.showCrafting,
      characterId: this.props.characterId,
      isDead: this.props.isDead,
      gold: this.props.characterGold,
    });

    this.craftingTimeOut.listen('Game.Core.Events.ShowCraftingTimeOutEvent', (event) => {
      this.setState({
        canCraft: event.canCraft,
        timeRemaining: event.canCraft ? 0 : 10,
      }, () => {
        this.props.updateCanCraft(event.canCraft);
      });
    });

    this.topBar.listen('Game.Core.Events.UpdateTopBarBroadcastEvent', (event) => {
      this.setState({
        gold: event.characterSheet.gold,
      });
    });

    this.craftingList.listen('Game.Skills.Events.UpdateCharacterCraftingList', (event) => {
      this.setState({
        itemsToCraft: event.items,
      });
    });
  }

  componentDidUpdate(prevProps, prevState) {
    if (this.props.showCrafting !== prevProps.showCrafting) {
      this.setState({
        showCrafting: this.props.showCrafting,
      });
    }

    if (this.props.isDead !== prevProps.isDead) {
      this.setState({
        isDead: this.props.isDead,
      });
    }

    if (this.props.shouldChangeCraftingType) {
      this.setState({
        craftingType: null,
        itemsToCraft: [],
        itemToCraft: 0,
      }, () => {
        this.props.changeCraftingType(false);
      });
    }

    if (this.props.characterGold !== prevProps.characterGold) {
      this.setState({
        gold: this.props.characterGold,
      });
    }

    if (prevState.itemsToCraft.length !== this.state.itemsToCraft.length && (this.state.itemsToCraft.length !== 0 && prevState.itemsToCraft.length !== 0)) {
      this.setState({
        showSuccess: true
      }, () => {
        setTimeout(() => {
          this.setState({
            showSuccess: false
          });
        }, 3000);
      })
    }
  }

  updateCraftingType(event) {
    this.setState({
      craftingType: event.target.value,
    }, () => {
      if (this.state.craftingType !== null) {
        axios.get('/api/crafting/' + this.state.characterId, {
          params: {
            crafting_type: this.state.craftingType
          }
        }).then((result) => {
          this.setState({
            itemsToCraft: result.data.items,
            itemToCraft: result.data.items[0].id,
          });
        }).catch((err) => {
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
      }
    });
  }

  buildCraftableItemsOptions() {
    if (this.state.itemsToCraft !== null) {
      return this.state.itemsToCraft.map((item) => {
        return <option key={item.id} value={item.id}>{item.name} --> Cost to craft: {item.cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")} Gold</option>
      });
    }
  }

  setItemToCraft(event) {
    this.setState({
      itemToCraft: parseInt(event.target.value),
    });
  }

  craft() {
    if (!this.state.canCraft) {
      return getServerMessage('cant_craft');
    }

    const foundItem = this.state.itemsToCraft.filter(item => item.id === this.state.itemToCraft)[0];

    this.setState({
      canCraft: this.state.gold < foundItem.cost ? true : false,
      showSuccess: false,
    }, () => {
      this.props.updateCanCraft(event.canCraft);

      axios.post('/api/craft/' + this.state.characterId, {
        item_to_craft: this.state.itemToCraft,
        type: this.state.craftingType,
      }).then((result) => {
        if (!_.isEmpty(result.data.items)) {
          this.setState({
            itemsToCraft: result.data.items
          });
        }
      }).catch((err) => {
        if (err.hasOwnProperty('response')) {
          const response = err.response;

          if (response.status === 401) {
            return location.reload();
          }

          if (response.status === 429) {
            // Reload to show them their notification.
            return this.props.openTimeOutModal();
          }
        }
      });
    });
  }

  renderCraftingDropDowns() {
    if (this.state.showCrafting) {
      if (this.state.craftingType === null) {
        return (
          <select className="form-control mt-2" id="crafting-type" name="crafting-type"
                  value={0}
                  onChange={this.updateCraftingType.bind(this)}
                  disabled={this.state.isDead}>
            <option value="" key="0">Please select a crafting type</option>
            <option value="weapon" key="weapon">Weapon</option>
            <option value="armour" key="armour">Armour</option>
            <option value="spell" key="spell">Spell</option>
            <option value="ring" key="ring">Ring</option>
            <option value="artifact" key="artifact">Artifact</option>
          </select>
        );
      }

      return (
        <select className="form-control mt-2" id="crafting" name="crafting"
                value={this.state.itemToCraft !== null ? this.state.itemToCraft : 1}
                onChange={this.setItemToCraft.bind(this)}
                disabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring}>
          {this.buildCraftableItemsOptions()}
        </select>
      );
    }
  }

  renderCraftingButton() {
    if (this.state.itemToCraft !== 0 && this.state.showCrafting) {
      return (
        <button className="btn btn-primary mt-2"
                type="button"
                disabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring}
                onClick={this.craft.bind(this)}
        >
          Craft!
        </button>
      );
    }

    return null;

  }

  render() {
    if (!this.state.showCrafting) {
      return null;
    }

    return (
      <>
        <div className={"row mb-2 mt-2 " + (!this.state.showSuccess ? 'hide' : '')}>
          <div className="col-md-10">
            <div className="alert alert-success">You got new items to craft! Check the list.</div>
          </div>
        </div>

        <Row>
          <Col xs={12} sm={12} md={8} lg={8} xl={6}>
            {this.renderCraftingDropDowns()}
          </Col>
          <Col xs={12} sm={12} md={4} lg={4} xl={6}>
            <Row>
              <Col xs={3}>
                {this.renderCraftingButton()}
              </Col>
              <Col xs={8}>
                {
                  this.state.itemToCraft !== 0 ?
                    <TimeOutBar
                      cssClass={'crafting-timeout'}
                      readyCssClass={'crafting-ready'}
                      timeRemaining={this.state.timeRemaining}
                      channel={'show-crafting-timeout-bar-' + this.props.userId}
                      eventClass={'Game.Core.Events.ShowCraftingTimeOutEvent'}
                    />
                    : null
                }
              </Col>
            </Row>
          </Col>
        </Row>
      </>
    )
  }
}
