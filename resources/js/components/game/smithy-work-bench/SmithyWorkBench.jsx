import React, {Fragment} from 'react';
import {Row, Col} from "react-bootstrap";
import TimeOutBar from "../timeout/timeout-bar";
import Select from "react-select";
import LockedLocationType from "../sections/lib/LockedLocationType";
import AlertError from "../components/base/alert-error";

export default class SmithyWorkBench extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      canCraft: true,
      isDead: false,
      isAdventuring: false,
      isCrafting: false,
      selectedItem: null,
      selectedAlchemyItem: null,
      lastUsedAlchemicalItem: null,
      errorMessage: null,
      totalGoldDustCost: 0,
      items: [],
      alchemyItems: [],
      timeRemaining: 0,
    }

    this.craftingTimeOut = Echo.private('show-crafting-timeout-bar-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/character/'+this.props.characterId+'/inventory/smiths-workbench').then((result) => {
      this.setState({
        loading: false,
        items: result.data.items,
        alchemyItems: result.data.alchemy,
      })
    }).catch((err) => {
      this.setState({
        loading: false
      });

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

    this.craftingTimeOut.listen('Game.Core.Events.ShowCraftingTimeOutEvent', (event) => {
      this.setState({
        canCraft: event.canCraft,
      }, () => {
        this.props.updateCanCraft(event.canCraft);
      });
    });
  }

  buildItemList(items) {
    if (items !== null) {
      return items.map((item) => {
        return <option value={item.item.id}>{item.item.affix_name} {item.item.can_use_on_other_items ? '' : ', Holy Stacks Applied: ' + item.item.holy_stacks_applied}</option>
      });
    }
  }

  setItemToApply(newValue) {
    const value        = parseInt(event.target.value);

    const foundSlot    = this.state.items.filter((i) => i.item.id === value);

    if (foundSlot.length > 0) {
      this.setState({
        selectedItem: foundSlot[0].item.id,
      }, () => {
        this.calculateCosts(foundSlot[0].item, null);
      });
    }
  }

  setAlchemyItemToUse(event) {
    const value        = parseInt(event.target.value);
    const foundSlot    = this.state.alchemyItems.filter((i) => i.item.id === value);
    const foundItem    = this.state.items.filter((i) => i.item.id === this.state.selectedItem);

    if (foundSlot.length > 0) {
      this.setState({
        selectedAlchemyItem: foundSlot[0].item.id,
      }, () => {
        this.calculateCosts(foundItem[0].item, foundSlot[0].item);
      });
    }
  }

  updateSelectedItems() {
    let foundItemWithStacks = this.state.items.filter((i) => i.item.id === this.state.selectedItem);
    let foundAlchemyItem    = this.state.alchemyItems.filter((i) => i.item.name === this.state.lastUsedAlchemicalItem);

    if (foundItemWithStacks.length === 0) {
      foundItemWithStacks = null;
    } else {
      foundItemWithStacks = foundItemWithStacks[0].item;
    }

    if (foundAlchemyItem.length === 0) {
      foundAlchemyItem = null;
    } else {
      foundAlchemyItem = foundAlchemyItem[0].item;
    }

    this.setState({
      selectedItem: foundItemWithStacks !== null ? foundItemWithStacks.id : null,
      selectedAlchemyItem: foundAlchemyItem !== null ? foundAlchemyItem.id : null,
    }, () => {
      this.calculateCosts(foundItemWithStacks, foundAlchemyItem);
    })
  }

  calculateCosts(item, alchemyItem) {
    if (this.state.selectedItem === null) {
      return this.setState({
        totalGoldDustCost: 0,
      });
    }

    let goldDustCost = item.holy_stacks * 10000;

    if (alchemyItem !== null) {

      goldDustCost *= alchemyItem.holy_level;
    }

    this.setState({
      totalGoldDustCost: goldDustCost
    });
  }

  canNotApplyHolyOil() {
    if (this.state.alchemyItems.length === 0) {
      return true;
    }

    if (this.state.items.length === 0) {
      return true;
    }

    if (this.props.characterGoldDust < this.state.totalGoldDustCost) {
      return true;
    }

    if (this.state.selectedItem === null) {
      return true;
    }

    if (this.state.selectedAlchemyItem === null) {
      return true;
    }

    return false
  }

  cannotApplyReason() {
    if (this.state.alchemyItems.length === 0) {
      return 'No alchemical items to use.';
    }

    if (this.state.items.length === 0) {
      return 'No items to apply oils to.';
    }

    if (this.props.characterGoldDust < this.state.totalGoldDustCost) {
      return 'Not enough gold dust.';
    }

    if (this.state.selectedItem === null) {
      return 'You must select an item (either you have no item selected, or the item was duplicated after the first holy oil and need to be re-selected).'
    }

    if (this.state.selectedAlchemyItem === null) {
      return 'You must select an alchemical item.';
    }

    return ''
  }

  applyOil() {


    let foundItemWithStacks = this.state.items.filter((i) => i.item.id === this.state.selectedItem);
    let foundAlchemyItem    = this.state.alchemyItems.filter((i) => i.item.id === this.state.selectedAlchemyItem);

    this.setState({
      isCrafting: true,
      lastUsedAlchemicalItem: foundAlchemyItem[0].item.name,
    });

    axios.post('/api/character/'+ this.props.characterId +'/smithy-workbench/apply', {
      item_id: foundItemWithStacks[0].item.id,
      alchemy_item_id: foundAlchemyItem[0].item.id,
      gold_dust_cost: this.state.totalGoldDustCost,
    }).then((result) => {
      this.setState({
        isCrafting: false,
        items: result.data.items,
        alchemyItems: result.data.alchemy,
      }, () => {
        this.updateSelectedItems();
      });
    }).catch((err) => {
      this.setState({
        isCrafting: false
      });

      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload()
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal()
        }

        if (response.data.hasOwnProperty('message')) {
          this.setState({
            errorMessage: response.data.message
          })
        }
      }
    })
  }

  render() {
    if (this.state.loading) {
      return <p className="mt-2">One moment ...</p>
    }

    const foundItem        = this.state.items.filter((i) => i.item.id === this.state.selectedItem);
    const foundAlchemyItem = this.state.alchemyItems.filter((i) => i.item.id === this.state.selectedAlchemyItem);

    return  (
      <Fragment>
        {
          this.state.errorMessage !== null ?
              <AlertError icon={"fas fa-exclamation"} title={'Oops!'}>
                <p>{this.state.errorMessage}</p>
              </AlertError>
          : null
        }
        <Row>
          <Col xs={12} sm={12} md={8} lg={8} xl={6}>
            <div className="mt-2">
              <label>Item to enhance:</label>
              <select className="form-control monster-select"
                      value={this.state.selectedItem}
                      onChange={this.setItemToApply.bind(this)}
                      disabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring}>
                <option value="" key="">Please select an item</option>
                {this.buildItemList(this.state.items)}
              </select>
            </div>
            <div className="mt-2">
              <label>Alchemical Item:</label>
              <select className="form-control monster-select"
                      value={this.state.selectedAlchemyItem}
                      onChange={this.setAlchemyItemToUse.bind(this)}
                      disabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring || this.state.selectedItem === null}>
                <option value="" key="">Please select an item</option>
                {this.buildItemList(this.state.alchemyItems)}
              </select>
            </div>
            <div className="mt-4">
              <dl>
                <dt>Gold Dust Cost:</dt>
                <dd>{this.state.totalGoldDustCost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</dd>
                <dt>Max Holy Stacks:</dt>
                <dd>{foundItem.length > 0 ? foundItem[0].item.holy_stacks : 0}</dd>
                <dt>Holy Stacks Left:</dt>
                <dd>{foundItem.length > 0 ? foundItem[0].item.holy_stacks - foundItem[0].item.holy_stacks_applied : 0}</dd>
                {
                  this.cannotApplyReason() !== '' ?
                    <Fragment>
                      <dt>Cannot Apply Because:</dt>
                      <dd className="tw-text-red-600">{this.cannotApplyReason()}</dd>
                    </Fragment>
                  : null
                }
              </dl>
              {
                this.state.selectedAlchemyItem !== null ?
                  <dl className="mt-2">
                    <dt>Item Stat Increase:</dt>
                    <dd>{LockedLocationType.getEffect(foundAlchemyItem[0].item.holy_level).stat_bonus_increase}</dd>
                    <dt>Devouring Darkness Increase:</dt>
                    <dd>{LockedLocationType.getEffect(foundAlchemyItem[0].item.holy_level).devouring_darkness_range}</dd>
                  </dl>
                : null
              }
            </div>
          </Col>
          <Col xs={12} sm={12} md={8} lg={8} xl={6}>
            <Row>
              <Col xs={3}>
                <button className="btn btn-primary mt-2"
                        type="button"
                        disabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring || this.canNotApplyHolyOil() || this.state.isCrafting}
                        onClick={this.applyOil.bind(this)}
                >
                  Apply! {this.state.isCrafting ? <i className="fas fa-spinner fa-spin"></i> : null}
                </button>
              </Col>
              <Col xs={8}>
                <TimeOutBar
                  cssClass={'crafting-timeout'}
                  readyCssClass={'crafting-ready'}
                  timeRemaining={this.state.timeRemaining}
                  channel={'show-crafting-timeout-bar-' + this.props.userId}
                  eventClass={'Game.Core.Events.ShowCraftingTimeOutEvent'}
                />
              </Col>
            </Row>
          </Col>
        </Row>
      </Fragment>
    );
  }

}
