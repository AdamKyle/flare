import React from 'react';
import TimeOutBar from '../timeout/timeout-bar';
import { getServerMessage } from '../helpers/server_message';

export default class EnchantingAction extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      affixList: [],
      inventoryList: [],
      itemToEnchant: null,
      suffixId: null,
      prefixId: null,
      canCraft: true,
      isDead: this.props.isDead,
      showEnchanting: false,
      chracterId: this.props.characterId,
      timeRemaining: this.props.timeRemaining,
      showSuccess: false,
      gold: 0,
      cost: 0,
      extraTime: null,
      isAdventuring: this.props.isAdventuring,
      loading: true,
    }

    this.craftingTimeOut = Echo.private('show-crafting-timeout-bar-' + this.props.userId);
  }

  componentDidMount() {
    this.setState({
      showEnchanting: this.props.showEnchanting,
      characterId: this.props.characterId,
      isDead: this.props.isDead,
      gold: this.props.characterGold,
    });

    this.craftingTimeOut.listen('Game.Core.Events.ShowCraftingTimeOutEvent', (event) => {
      console.log(event);
      this.setState({
        canCraft:      event.canCraft,
        timeRemaining: event.canCraft ? 0 : 10,
      }, () => {
        this.props.updateCanCraft(event.canCraft);
      });
    });

    axios.get('/api/enchanting/' + this.props.characterId).then((result) => {
      this.setState({
        affixList: result.data.affixes,
        inventoryList: result.data.character_inventory,
        loading: false,
      });
    }).catch((error) => {
        console.error(error);
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
  }

  enchant() {
    const affixesToAttach = [];
    const prefixId        = this.state.prefixId;
    const suffixId        = this.state.suffixId;

    if ((prefixId !== 0 && prefixId !== null)) {
      affixesToAttach.push(prefixId);
    }

    if ((suffixId !== 0 && suffixId !== null)) {
      affixesToAttach.push(suffixId);
    }

    const params = {
      slot_id: this.state.inventoryList.filter((s) => s.item.id === this.state.itemToEnchant)[0].id,
      affix_ids: affixesToAttach,
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
    }).catch((error) => {
      console.error(error.response);
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
    let cost = foundAffix.cost;
    let time = null;

    if (itemToEnchant !== null && itemToEnchant !== 0) {
      const foundSlot = this.state.inventoryList.filter((i) => i.item_id === itemToEnchant)[0];

      if (foundSlot.item.item_prefix !== null) {
        cost += (foundSlot.item.item_prefix.cost / 2)
      }
    }

    return cost;
  }

  getSuffixCost(foundAffix, itemToEnchant) {
    let cost = foundAffix.cost;

    if (itemToEnchant !== null && itemToEnchant !== 0) {
      const foundSlot = this.state.inventoryList.filter((i) => i.item_id === itemToEnchant)[0];

      if (foundSlot.item.item_suffix !== null) {
        
        cost += (foundSlot.item.item_suffix.cost / 2)
      }
    }

    return cost;
  }

  setPrefixId(event) {
    const value      = parseInt(event.target.value);
    const oldPrefix  = this.state.affixList.filter((a) => a.id === this.state.prefixId)[0];
    let foundAffix   = null;
    let cost         = this.state.cost;

    if ((value === 0 || value !== 0) && (this.state.prefixId !== 0 && this.state.prefixId !== null) && (this.state.itemToEnchant !== 0 && this.state.itemToEnchant !== null) && typeof oldPrefix !== "undefined") {
      console.log('I am in here ....');
      foundAffix          = this.state.affixList.filter((a) => a.id === value)[0];
      cost                -= this.getPrefixCost(oldPrefix, this.state.itemToEnchant);
      cost                += this.getPrefixCost(foundAffix, this.state.itemToEnchant);
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
    const value      = parseInt(event.target.value);
    const oldSuffix  = this.state.affixList.filter((a) => a.id === this.state.suffixId)[0];
    let foundAffix   = null;
    let cost         = this.state.cost;

    if ((value === 0 || value !== 0) && (this.state.suffixId !== 0 && this.state.suffixId !== null) && (this.state.itemToEnchant === 0 && this.state.itemToEnchant === null) && typeof oldSuffix !== 'undefined') {
      foundAffix           = this.state.affixList.filter((a) => a.id === value)[0];
      cost                -= this.getPrefixCost(oldSuffix, this.state.itemToEnchant);
      cost                += this.getPrefixCost(foundAffix, this.state.itemToEnchant);
    } else if ((this.state.itemToEnchant !== 0 || this.state.itemToEnchant !== null)) {
      foundAffix           = this.state.affixList.filter((a) => a.id === value)[0];
      cost                += this.getSuffixCost(foundAffix, this.state.itemToEnchant);
    } else if ((this.state.itemToEnchant === 0 || this.state.itemToEnchant === null)) {
      cost = 0;
    }

    this.setState({
      suffixId: parseInt(event.target.value),
      cost: cost
    });
  }

  buildInventoryOptions() {
    return this.state.inventoryList.map((slot) => {
        return (<option key={'item-' + slot.id} value={slot.item.id}>{slot.item.affix_name}</option>);
    });
  }

  buildSuffixOptions() {
    return this.state.affixList.map((affix) => {
      if (affix.type === 'suffix') {
      return (<option key={'suffix-'+affix.id} value={affix.id}>{affix.name} --> Cost in gold: {affix.cost}</option>);
      }
    });
  }

  buildPrefixOptions() {
    return this.state.affixList.map((affix) => {
      if (affix.type === 'prefix') {
      return (<option key={'prefix-'+affix.id} value={affix.id}>{affix.name} --> Cost in gold: {affix.cost}</option>);
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
    if (!this.state.showEnchanting) {
      return null;
    }

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
        <div className={"container justify-content-center"}>
           <div className="row">
             <div className="col-md-10">
               <div className="alert alert-danger">You don't have the required intelligence to enchant.</div>
             </div>
           </div>
         </div>
      );
    }

    return (
      <>
        <div className={"container justify-content-center " + (_.isEmpty(this.state.inventoryList) && !this.state.loading ? '' : 'hide')}>
          <div className="row">
            <div className="col-md-10">
              <div className="alert alert-danger">You have no items to enchant.</div>
            </div>
          </div>  
        </div>
        
        <div className="form-group row">
          <div className="col-md-3">
            <select 
              className="form-control ml-3 mt-2" id="crafting" name="crafting"
              value={this.state.itemToEnchant !== null ? this.state.itemToEnchant : 0}
              onChange={this.setItemToEnchant.bind(this)}
              disabled={this.state.isDead || !this.state.canCraft || this.state.isAdventuring || _.isEmpty(this.state.inventoryList)}
            >
              <option key={'item-0'} value="0">Please Select Item</option>
              {this.buildInventoryOptions()}
            </select>
          </div>
          { 
            this.state.affixList.filter((a) => a.type === 'prefix').length > 0
            ?
            <div className={this.state.affixList.filter((a) => a.type === 'suffix').length === 0 ? "col-md-4" : "col-md-3"}>
              <select 
                className="form-control ml-3 mt-2" id="crafting" name="crafting"
                value={this.state.prefixId !== null ? this.state.prefixId : 0}
                onChange={this.setPrefixId.bind(this)}
                disabled={this.state.isDead || !this.state.canCraft || this.state.itemToEnchant === null || this.state.itemToEnchant === 0 || this.state.isAdventuring}
              >
                <option key={'prefix-0'} value="0">Please Select Prefix</option>
                {this.buildPrefixOptions()}
              </select>
            </div>
            : null
          }
          {
            this.state.affixList.filter((a) => a.type === 'suffix').length > 0
            ?
            <div className={this.state.affixList.filter((a) => a.type === 'prefix').length === 0 ? "col-md-4" : "col-md-2"}>
              <select 
                className="form-control ml-3 mt-2" id="crafting" name="crafting"
                value={this.state.suffixId !== null ? this.state.suffixId : 0}
                onChange={this.setSuffixId.bind(this)}
                disabled={this.state.isDead || !this.state.canCraft || this.state.itemToEnchant === null || this.state.itemToEnchant === 0 || this.state.isAdventuring}
              >
                <option key={'affix-0'} value="0">Please Select Suffix</option>
                {this.buildSuffixOptions()}
              </select>
            </div>
            : null
          }
          <div className="col-md-1">
            {this.renderEnchantingButton()}
          </div>
          <div className="col-md-3">
            <div className="ml-4 mt-3">
              {this.state.itemToCraft !== 0 ?
                <TimeOutBar
                  turnOffFloat={true}
                  cssClass={'character-timeout enchanting'}
                  readyCssClass={'character-ready enchanting'}
                  timeRemaining={this.state.timeRemaining}
                  channel={'show-crafting-timeout-bar-' + this.props.userId}
                  eventClass={'Game.Core.Events.ShowCraftingTimeOutEvent'}
                />
                : null
              }
            </div>
          </div>
        </div>
        {
          this.state.cost !== 0
          ?
          <div className="form-group row">
            <div className="col-md-12 ml-3">
                <strong>Cost: </strong> {this.state.cost}
            </div>
          </div>
          : null
        }
      </>
    )
  }
} 