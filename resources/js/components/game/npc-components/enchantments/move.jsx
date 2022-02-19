import React, {Fragment} from 'react';
import AlertSuccess from "../../components/base/alert-success";
import AlertError from "../../components/base/alert-error";

export default class Move extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      queensProcessing: false,
      successMessage: null,
      errorMessage: null,
      formOptions: {
        selected_slot_id: 0,
        selected_affix: 0,
        selected_secondary_slot_id: 0,
        gold_cost: 0,
        shard_cost: 0,
      },
      affixesToSelectFrom: [],
      canMove: false,
    }
  }

  moveEnchantments() {
    this.setState({
      errormessage: null,
      queenProcessing: true,
      successMessage: null,
    });

    axios.post('/api/character/'+this.props.characterId+'/random-enchant/move', this.state.formOptions).then((result) => {
      this.setState({
        queenProcessing: false,
        successMessage: result.data.message,
      }, () => {
        this.setState({
          formOptions: {
            selected_slot_id: 0,
            selected_affix: 0,
            selected_secondary_slot_id: 0,
            gold_cost: 0,
            shard_cost: 0,
          }
        }, () => {
          this.props.updateCurrencies(
            result.data.gold_dust, result.data.shards
          );

          this.props.fetchUniqueData();
        })
      });
    }).catch((err) => {
      this.setState({queenProcessing: false});

      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal();
        }

        if (response.status === 422) {
          this.setState({
            errorMessage: response.data.message,
          });
        }
      }
    })
  }

  renderInventorySelectOptions() {
    return this.props.uniquesOnHand.map((slot) => {
      return <option value={slot.id}>{slot.item.affix_name}</option>
    })
  }

  renderMoveToInventorySelectOptions() {
    return this.props.nonUniques.map((slot) => {
      return <option value={slot.id}>{slot.item.affix_name}</option>
    })
  }

  renderAffixSelectOptions() {
    return this.state.affixesToSelectFrom.map((affix) => {
      return <option value={affix.type}>{affix.name}, Type: {affix.type}</option>
    });
  }

  updateSelectedSlot(event) {
    const slotId = parseInt(event.target.value) || 0;
    const formOptions = JSON.parse(JSON.stringify(this.state.formOptions));

    formOptions.selected_slot_id = slotId;

    const foundItem = this.props.uniquesOnHand.filter((slot) => slot.id === slotId)[0];

    const selectableOptions = [];

    if (foundItem.item.item_prefix_id !== null) {
      if (foundItem.item.item_prefix.randomly_generated) {
        selectableOptions.push(foundItem.item.item_prefix)
      }
    }

    if (foundItem.item.item_suffix_id !== null) {
      if (foundItem.item.item_suffix.randomly_generated) {
        selectableOptions.push(foundItem.item.item_suffix)
      }
    }

    this.setState({
      formOptions: formOptions,
      affixesToSelectFrom: selectableOptions,
    }, () => {
      this.updateCost();
    });
  }

  updateSelectedSecondarySlot(event) {
    const slotId = parseInt(event.target.value) || 0;
    const formOptions = JSON.parse(JSON.stringify(this.state.formOptions));

    formOptions.selected_secondary_slot_id = slotId;

    this.setState({
      formOptions: formOptions,
    });
  }

  updateSelectedAffix(event) {
    const selectedAffixType = event.target.value
    const formOptions       = JSON.parse(JSON.stringify(this.state.formOptions));

    if (selectedAffixType !== '0') {
      formOptions.selected_affix = selectedAffixType;

      this.setState({
        formOptions: formOptions
      }, () => {
        this.updateCost();
      });
    } else {
      formOptions.selected_affix = 0;

      this.setState({
        formOptions: formOptions,
      }, () => {
        this.updateCost()
      })
    }
  }

  updateCost() {

    const formOptions = JSON.parse(JSON.stringify(this.state.formOptions));

    if (this.state.formOptions.selected_affix === 0) {
      formOptions.gold_cost  = 0;
      formOptions.shard_cost = 0;

      return this.setState({
        formOptions: formOptions,
        canAfford: false,
      })
    }

    const item = this.props.uniquesOnHand.filter((slot) => slot.id === this.state.formOptions.selected_slot_id)[0].item;

    let cost = 0;

    if (formOptions.selected_affix === 'all-enchantments') {
      cost += item.item_prefix_id !== null ? item.item_prefix.cost : 0;
      cost += item.item_suffix_id !== null ? item.item_suffix.cost : 0;
    } else {
      cost += item['item_' + formOptions.selected_affix + '_id'] !== null ? item['item_' + formOptions.selected_affix].cost : 0
    }

    let shardCost = cost * .00000002;

    if (shardCost < 0.0) {
      shardCost = 10;
    }

    formOptions.gold_cost = parseInt(cost.toFixed(0));
    formOptions.shard_cost = parseInt(shardCost.toFixed(0));

    if (cost > this.props.currencies.gold) {
      return this.setState({
        errorMessage: 'No no no! I wont play these poor people games with you child! (not enough gold).',
        canMove: false,
        formOptions: formOptions,
      });
    }

    if (shardCost > this.props.currencies.shards) {
      return this.setState({
        errorMessage: 'So close child. You seem to be missing something though... (not enough shards).',
        canMove: false,
        formOptions: formOptions,
      });
    }

    this.setState({
      errormessage: null,
      canMove: true,
      formOptions: formOptions,
    });
  }

  formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  canMove() {
    return this.state.canMove &&
           this.state.formOptions.selected_slot_id !== 0 &&
           this.state.formOptions.selected_affix !== 0 &&
           this.state.selected_secondary_slot_id !== 0;
  }

  render() {
    return (
      <Fragment>
        {
          this.state.queenProcessing ?
            <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
              <div className="progress-bar progress-bar-striped indeterminate">
              </div>
            </div>
            : null
        }

        <div className="row">
          <div className="col-md-6">
            {
              this.state.errorMessage !== null ?
                <AlertError icon={"fas fa-exclamation"} title={'You displease the Queen'}>
                  <p>
                    {this.state.errorMessage}
                  </p>
                </AlertError>
                : null
            }
            {
              this.state.successMessage !== null ?
                <AlertSuccess icon={"fas fa-check-circle"} title={'Look at you beautiful!'}>
                  <p><em>Oooooh hooo hoo hoo!</em> The queen looks very pleased with her self. She is beaming with joy! <em>Here ya go child! ooooh hooo hoo hoo!</em></p>
                  <p>{this.state.successMessage}</p>
                  <p className="mt-2">
                    <strong>Note</strong>: If you have the item open in a new tab, refreshing that tab will not show you the new details. We have essentially generated a new item.
                  </p>
                </AlertSuccess>
                : null
            }
            <div className="form-group">
              <label htmlFor="enchantment-valuation" className="mr-2">Select Item</label>
              <select className="form-control mr-2" id="enchantment-valuation" value={this.state.formOptions.selected_slot_id} onChange={this.updateSelectedSlot.bind(this)}>
                <option key={''} value={''}>Please Select</option>
                {this.renderInventorySelectOptions()}
              </select>
            </div>
            <div className={this.state.formOptions.selected_slot_id === 0 ? 'hide' : "form-group"}>
              <label htmlFor="enchantment-valuation" className="mr-2">Select affix to move</label>
              <select className="form-control mr-2" id="enchantment-valuation" value={this.state.formOptions.selected_affix} onChange={this.updateSelectedAffix.bind(this)}>
                <option key={''} value={''}>Please Select</option>
                {this.renderAffixSelectOptions()}
                <option key={'move-both-affixes'} value={'all-enchantments'}>Both</option>
              </select>
              <p className="mt-2">
                You cannot move no unique affixes fro items, selecting both on an item that has one unique and one normal, will only move the unique over.
              </p>
            </div>
            <div className={this.state.formOptions.selected_affix === 0 ? 'hide' : "form-group"}>
              <label htmlFor="enchantment-valuation" className="mr-2">Select item to move to</label>
              <select className="form-control mr-2" id="enchantment-valuation" value={this.state.formOptions.selected_secondary_slot_id} onChange={this.updateSelectedSecondarySlot.bind(this)}>
                <option key={''} value={''}>Please Select</option>
                {this.renderMoveToInventorySelectOptions()}
              </select>
              <p className="mt-2">This will replace any existing affix(es) you may have on the item. There is no additional cost in selecting the item,
                the cost comes directly from the unique you wish to transfer. Do note, this new item will be considered a unique and will follow the same set and equipping rules as well as
              market board rules.</p>
            </div>
            <hr />
            <dl>
              <dt>Your Gold:</dt>
              <dd>{this.formatNumber(this.props.currencies.gold)}</dd>
              <dt>Your Shards:</dt>
              <dd>{this.formatNumber(this.props.currencies.shards)}</dd>
              <dt>Cost (Gold Dust)</dt>
              <dd className={this.state.canMove ? 'text-success' : 'text-danger'}>{this.formatNumber(this.state.formOptions.gold_cost)}</dd>
              <dt>Cost (Shards)</dt>
              <dd className={this.state.canMove ? 'text-success' : 'text-danger'}>{this.formatNumber(this.state.formOptions.shard_cost)}</dd>
            </dl>
            <hr />
            <button className="btn btn-primary" disabled={!this.canMove()} onClick={this.moveEnchantments.bind(this)}>Move</button>
          </div>
          <div className="col-md-6">
            <h3 className="tw-font-light">Help</h3>
            <hr />
            <p>
              Here you will select one of your uniques.
            </p>
            <p>
              Next you will select, once you select a unique, one or both of the enchantments.
            </p>
            <p>
              Finally select an item to move one or both enchants to. This will <strong>replace</strong> any existing enchants.
              You cannot move crafted enchants, only unique (randomly generated) enchants. You can move them as many times as you can afford.
              You also can move one unique, replacing one enchantment and still retain the other enchantment - This item would still be considered a unique.
            </p>
            <p>
              The cost break down is as such: Affix(es) cost (ie, 100 billion + 100 billion for a unique with two enchants at a valuation of 100 billion) for the gold. while the shard cost is 0.000002% of the total
              gold cost, never falling below 10 shards. This can mean a 100 billion unique with one enchant will cost ~2k in shards to move and 100 billion in
              gold.
            </p>
            <p>
              For more info, please read up on <a href="/information/random-enchants">Uniques (AKA: Random Enchants)</a>.
            </p>
          </div>
        </div>
      </Fragment>
    )
  }
}
