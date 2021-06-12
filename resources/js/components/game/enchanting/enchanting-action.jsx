import React from 'react';
import {Row, Col} from 'react-bootstrap';
import TimeOutBar from '../timeout/timeout-bar';

export default class EnchantingAction extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      affixList: [],
      inventoryList: [],
      itemToEnchant: "",
      suffixId: "",
      prefixId: "",
      canCraft: true,
      isDead: this.props.isDead,
      showEnchanting: false,
      characterId: this.props.characterId,
      timeRemaining: this.props.timeRemaining,
      showSuccess: false,
      gold: 0,
      cost: 0,
      extraTime: null,
      isAdventuring: this.props.isAdventuring,
      loading: true,
    }

    this.craftingTimeOut = Echo.private('show-crafting-timeout-bar-' + this.props.userId);
    this.topBar = Echo.private('update-top-bar-' + this.props.userId);
  }

  componentDidMount() {
    this.setState({
      showEnchanting: this.props.showEnchanting,
      characterId: this.props.characterId,
      isDead: this.props.isDead,
      gold: this.props.characterGold,
    });

    this.craftingTimeOut.listen('Game.Core.Events.ShowCraftingTimeOutEvent', (event) => {
      this.setState({
        canCraft: event.canCraft,
        timeRemaining: event.canCraft ? 0 : event.timeout,
      }, () => {
        this.props.updateCanCraft(event.canCraft);
      });
    });

    this.topBar.listen('Game.Core.Events.UpdateTopBarBroadcastEvent', (event) => {
      this.setState({gold: event.characterSheet.gold});
    });

    axios.get('/api/enchanting/' + this.props.characterId).then((result) => {
      this.setState({
        affixList: result.data.affixes,
        inventoryList: result.data.character_inventory,
        loading: false,
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }
      }
    });
  }

  componentDidUpdate(prevProps, prevState) {
    if (this.props.showEnchanting !== prevProps.showEnchanting) {
      this.setState({
        showEnchanting: this.props.showEnchanting,
      });
    }

    if (this.props.isDead !== prevProps.isDead) {
      this.setState({
        isDead: this.props.isDead,
      });
    }

    if (this.props.characterGold !== prevProps.characterGold) {
      this.setState({
        gold: this.props.characterGold,
      });
    }

    if (this.state.isAdventuring !== this.props.isAdventuring) {

      this.setState({
        isAdventuring: this.props.isAdventuring,
      });
    }

    if (prevState.affixList.length !== this.state.affixList.length && (this.state.affixList.length !== 0 && prevState.affixList.length !== 0)) {
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

  enchant() {
    const affixesToAttach = [];
    const prefixId = this.state.prefixId;
    const suffixId = this.state.suffixId;

    if ((prefixId !== 0 && prefixId !== null)) {
      affixesToAttach.push(prefixId);
    }

    if ((suffixId !== 0 && suffixId !== null)) {
      affixesToAttach.push(suffixId);
    }

    const params = {
      slot_id: this.state.inventoryList.filter((s) => s.item.id === this.state.itemToEnchant)[0].id,
      affix_ids: affixesToAttach.filter((affix) => { return affix !== ""; }),
      cost: this.state.cost,
    }

    axios.post('/api/enchant/' + this.props.characterId, params).then((result) => {
      this.setState({
        affixList: result.data.affixes,
        inventoryList: result.data.character_inventory,
        itemToEnchant: 0,
        prefixId: 0,
        suffixId: 0,
        cost: 0,
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          // Reload to show them their notification.
          location.reload();
        }
      }
    });

  }

  setItemToEnchant(event) {
    let cost       = 0;
    const value    = parseInt(event.target.value);
    let foundAffix = null;

    if (this.state.suffixId !== null && this.state.suffixId !== 0) {
      foundAffix = this.state.affixList.filter((a) => a.id === this.state.suffixId)[0];

      if (typeof foundAffix !== 'undefined') {
        cost += this.getSuffixCost(foundAffix, value);
      }

    }

    if (this.state.prefixId !== null && this.state.prefixId !== 0) {
      foundAffix = this.state.affixList.filter((a) => a.id === this.state.prefixId)[0];

      if (typeof foundAffix !== 'undefined') {
        cost += this.getPrefixCost(foundAffix, value);
      }
    }

    this.setState({
      itemToEnchant: value,
      cost: cost,
    });
  }

  getPrefixCost(foundAffix, itemToEnchant) {

    if (typeof foundAffix === 'undefined') {
      return 0;
    }

    let cost = foundAffix.cost;
    let time = null;

    if (itemToEnchant !== null && itemToEnchant !== 0) {
      const foundSlot = this.state.inventoryList.filter((i) => i.item_id === itemToEnchant)[0];

      if (foundSlot.item.item_prefix !== null) {
        cost += 1000
      }
    }

    return cost;
  }

  getSuffixCost(foundAffix, itemToEnchant) {
    if (typeof foundAffix === 'undefined') {
      return 0;
    }

    let cost = foundAffix.cost;

    if (itemToEnchant !== null && itemToEnchant !== 0) {
      const foundSlot = this.state.inventoryList.filter((i) => i.item_id === itemToEnchant)[0];

      if (foundSlot.item.item_suffix !== null) {

        cost += 1000
      }
    }

    return cost;
  }

  setPrefixId(event) {
    const value = parseInt(event.target.value);
    const oldPrefix = this.state.affixList.filter((a) => a.id === this.state.prefixId)[0];
    let foundAffix = null;
    let cost = this.state.cost;

    if ((value === 0 || value !== 0) && (this.state.prefixId !== 0 && this.state.prefixId !== null) && (this.state.itemToEnchant !== 0 && this.state.itemToEnchant !== null) && typeof oldPrefix !== "undefined") {
      foundAffix = this.state.affixList.filter((a) => a.id === value)[0];
      cost -= this.getPrefixCost(oldPrefix, this.state.itemToEnchant);
      cost += this.getPrefixCost(foundAffix, this.state.itemToEnchant);
    } else if ((this.state.itemToEnchant !== 0 || this.state.itemToEnchant !== null)) {
      foundAffix = this.state.affixList.filter((a) => a.id === value)[0];

      cost += this.getPrefixCost(foundAffix, this.state.itemToEnchant);
    } else if ((this.state.itemToEnchant === 0 || this.state.itemToEnchant === null)) {
      cost = 0;
    }

    this.setState({
      prefixId: parseInt(event.target.value),
      cost: cost
    });
  }

  setSuffixId(event) {
    const value     = parseInt(event.target.value);
    const oldSuffix = this.state.affixList.filter((a) => a.id === this.state.suffixId)[0];
    let foundAffix  = null;
    let cost        = this.state.cost;

    if ((value === 0 || value !== 0) && (this.state.suffixId !== 0 && this.state.suffixId !== null) && (this.state.itemToEnchant === 0 && this.state.itemToEnchant === null) && typeof oldSuffix !== 'undefined') {
      foundAffix = this.state.affixList.filter((a) => a.id === value)[0];
      cost -= this.getSuffixCost(oldSuffix, this.state.itemToEnchant);
      cost += this.getSuffixCost(foundAffix, this.state.itemToEnchant);
    } else if ((this.state.itemToEnchant !== 0 || this.state.itemToEnchant !== null)) {
      foundAffix = this.state.affixList.filter((a) => a.id === value)[0];

      if (typeof foundAffix === 'undefined') {
        const foundOldAffix = this.state.affixList.filter((a) => a.id === this.state.suffixId)[0];

        cost -= this.getSuffixCost(foundOldAffix, this.state.itemToEnchant);
      } else {
        cost += this.getSuffixCost(foundAffix, this.state.itemToEnchant);
      }
    } else if ((this.state.itemToEnchant === 0 || this.state.itemToEnchant === null)) {
      cost = 0;
    }

    this.setState({
      suffixId: parseInt(event.target.value),
      cost: cost
    });
  }

  buildInventoryOptions() {
    const inventory = this.state.inventoryList;
    const newInventory = [];

    for (const slot of inventory) {
      if (slot.item.item_prefix === null && slot.item.item_suffix == null) {
        newInventory.unshift(slot);
      } else {
        newInventory.push(slot);
      }
    }

    return newInventory.map((slot) => {
      return (<option key={'item-' + slot.id} value={slot.item.id}>{slot.item.affix_name}</option>);
    });
  }

  buildSuffixOptions() {
    return this.state.affixList.map((affix) => {
      if (affix.type === 'suffix') {
        return (
          <option key={'suffix-' + affix.id} value={affix.id}>{affix.name} --> Cost in gold: {affix.cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</option>);
      }
    });
  }

  buildPrefixOptions() {
    return this.state.affixList.map((affix) => {
      if (affix.type === 'prefix') {
        return (
          <option key={'prefix-' + affix.id} value={affix.id}>{affix.name} --> Cost in gold: {affix.cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</option>);
      }
    });
  }

  isButtonDisabled() {
    if (this.state.isDead) {
      return true;
    }

    if (!this.state.canCraft) {
      return true;
    }

    if ((this.state.itemToEnchant === 0 || this.state.itemToEnchant === null)) {
      return true;
    }

    if ((this.state.prefixId === 0 || this.state.prefixId === null) && (this.state.suffixId === 0 || this.state.suffixId === null)) {
      return true;
    }

    if (this.state.gold < this.state.cost) {
      return true;
    }

    return this.state.isAdventuring;
  }


  renderEnchantingButton() {
    return (
      <button className="btn btn-primary mt-2"
              type="button"
              disabled={this.isButtonDisabled()}
              onClick={this.enchant.bind(this)}
      >
        Enchant!
      </button>
    );
  }

  render() {

    if (this.state.loading) {
      return (
        <div className={"container justify-content-center " + (!this.state.loading ? 'hide' : '')}>
          <div className="row">
            <div className="col-md-10 text-align-center text-info">
              Loading please wait ...
            </div>
          </div>
        </div>
      );
    }

    if (_.isEmpty(this.state.affixList)) {
      return (
        <div className="row mt-2">
          <div className="col-md-10">
            <div className="alert alert-danger">You don't have the required intelligence to enchant.</div>
          </div>
        </div>
      );
    }

    return (
      <>
        <div className={"row mt-3 " + (_.isEmpty(this.state.inventoryList) && !this.state.loading ? '' : 'hide')}>
          <div className="col-md-10">
            <div className="alert alert-danger">You have no items to enchant.</div>
          </div>
        </div>

        <div className={"row mb-3 " + (!this.state.showSuccess ? 'hide' : '')}>
          <div className="col-md-10">
            <div className="alert alert-success">You got new affixes to enchant! Check the lists.</div>
          </div>
        </div>

        <Row>
          <Col lg={12} xl={8}>
            <Row>
              <Col xs={12} sm={12} md={12} lg={12} xl={4}>
                <select
                  className="form-control mt-2" id="crafting" name="crafting"
                  value={this.state.itemToEnchant}
                  onChange={this.setItemToEnchant.bind(this)}
                  disabled={this.state.isDead || !this.state.canCraft || this.state.isAdventuring || _.isEmpty(this.state.inventoryList)}
                >
                  <option key={'item-0'} value="">Please Select Item</option>
                  {this.buildInventoryOptions()}
                </select>
              </Col>
              <Col xs={12} sm={12} md={12} lg={12} xl={4}>
                <select
                  className="form-control mt-2" id="crafting" name="crafting"
                  value={this.state.prefixId}
                  onChange={this.setPrefixId.bind(this)}
                  disabled={this.state.isDead || !this.state.canCraft || this.state.itemToEnchant === null || this.state.itemToEnchant === 0 || this.state.isAdventuring}
                >
                  <option key={'prefix-0'} value="">Please Select Prefix</option>
                  {this.buildPrefixOptions()}
                </select>
              </Col>
              <Col xs={12} sm={12} md={12} lg={12} xl={4}>
                <select
                  className="form-control mt-2" id="crafting" name="crafting"
                  value={this.state.suffixId}
                  onChange={this.setSuffixId.bind(this)}
                  disabled={this.state.isDead || !this.state.canCraft || this.state.itemToEnchant === null || this.state.itemToEnchant === 0 || this.state.isAdventuring}
                >
                  <option key={'affix-0'} value="">Please Select Suffix</option>
                  {this.buildSuffixOptions()}
                </select>
              </Col>
            </Row>
          </Col>
          <Col lg={12} xl={4}>
            <Row>
              <Col xs={3} sm={3} md={3} lg={3} xl={3}>
                {this.renderEnchantingButton()}
              </Col>
              <Col xs={6} sm={6} md={6} lg={6} xl={6}>
                <div className="ml-4 mt-3">
                  <TimeOutBar
                    turnOffFloat={true}
                    cssClass={'enchanting-timeout'}
                    readyCssClass={'enchanting-ready'}
                    timeRemaining={this.state.timeRemaining}
                    channel={'show-crafting-timeout-bar-' + this.props.userId}
                    eventClass={'Game.Core.Events.ShowCraftingTimeOutEvent'}
                  />
                </div>
              </Col>
            </Row>
          </Col>
        </Row>
        <Row>
          <Col xs={12}>
            <div className="mt-2">
              <strong>Cost: </strong> {this.state.cost} {this.state.cost > this.state.gold ? <span className="text-danger">You don't have the gold</span> : null}
            </div>
          </Col>
        </Row>
      </>
    )
  }
}
