import React, {Fragment} from 'react';
import AlertError from "../../components/base/alert-error";
import AlertSuccess from "../../components/base/alert-success";

export default class ReRoll extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      queenProcessing: false,
      errorMessage: null,
      successMessage: null,
      affixesToSelectFrom: [],
      canAfford: false,
      actionsToTake: {
        'base' : 'Re rolls the base aspect of the enchantment(s). Will not re roll the name of the enchantment how ever.',
        'stats': 'Re rolls the stats portion of the affix, including enemy stat reductions.',
        'skills': 'Re Rolls the skills aspect including enemy skill reduction.',
        'damage': 'Re rolls the damage aspect of the enchantment, this includes weather the damage can stack  and is irresistible.',
        'resistance': 'Re rolls the resistance aspect of the enchantment.',
        'everything': 'Re rolls everything about the enchantment accept for it\'s name.',
      },
      formOptions: {
        selected_slot_id: 0,
        selected_affix: 0,
        selected_reroll_type: '',
        shard_cost: 0,
        gold_dust_cost: 0,
      },
      selectedActionDescription: '',
    }
  }

  reRollEnchantment() {
    this.setState({
      errorMessage: null,
      queenProcessing: true,
      successMessage: null,
    });

    axios.post('/api/character/'+this.props.characterId+'/random-enchant/reroll', this.state.formOptions).then((result) => {
      this.setState({
        queenProcessing: false,
        successMessage: result.data.message,
      }, () => {
        this.props.updateCurrencies(
          result.data.gold_dust, result.data.shards
        );
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

  canReRoll() {
    return this.state.canAfford && this.state.formOptions.selected_slot_id !== 0 &&
      this.state.formOptions.selected_affix != 0 && this.state.formOptions.selected_reroll_type !== '';
  }

  formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  renderInventorySelectOptions() {
    return this.props.uniquesOnHand.map((slot) => {
      return <option value={slot.id}>{slot.item.affix_name}</option>
    })
  }

  renderAffixSelectOptions() {
    return this.state.affixesToSelectFrom.map((affix) => {
      return <option value={affix.type}>{affix.name}, Type: {affix.type}</option>
    });
  }

  renderReRollSelectOptions() {
    const options = [];

    for (const key in this.state.actionsToTake) {
      options.push(
        <option value={key}>{key}</option>
      );
    }

    return options
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

  updateReRollType(event) {
    const selectedType = event.target.value
    const formOptions  = JSON.parse(JSON.stringify(this.state.formOptions));

    if (selectedType !== '') {
      formOptions.selected_reroll_type = selectedType;

      let description = this.state.actionsToTake[selectedType];

      this.setState({
        formOptions: formOptions,
        selectedActionDescription: description,
      }, () => {
        this.updateCost();
      });
    } else {

      formOptions.selected_reroll_type = '';

      this.setState({
        formOptions: formOptions,
        selectedActionDescription: '',
      }, () => {
        this.updateCost();
      });
    }
  }

  updateCost() {

    const formOptions = JSON.parse(JSON.stringify(this.state.formOptions));

    if (this.state.formOptions.selected_affix === 0) {
      formOptions.gold_dust_cost = 0;
      formOptions.shard_cost     = 0;

      return this.setState({
        formOptions: formOptions,
        canAfford: false,
      })
    }

    let goldDustCost = 10000;
    let shardCost    = 100;
    const currencies = this.props.currencies;

    if (this.state.formOptions.selected_affix === 'all-enchantments') {
      goldDustCost = goldDustCost * 2;
      shardCost    = shardCost * 2;
    }

    if (this.state.formOptions.selected_reroll_type === 'everything') {
      goldDustCost = goldDustCost * 2;
      shardCost    = shardCost * 2;
    } else if (this.state.formOptions.selected_reroll_type !== '') {
      goldDustCost += goldDustCost * .166666
      shardCost    += shardCost * .166666
    }

    const cantAfford = (goldDustCost > currencies.gold_dust || shardCost > currencies.shard_cost);

    formOptions.gold_dust_cost = parseInt(goldDustCost.toFixed(0));
    formOptions.shard_cost     = parseInt(shardCost.toFixed(0));

    if (!cantAfford) {
      this.setState({
        errorMessage: null,
        formOptions: formOptions,
        canAfford: true,
      })
    } else {
      this.setState({
        formOptions: formOptions,
        cantAfford: false,
        errorMessage: 'Child... child... I am tiring of these games child. Is there anything you can buy that will make me love you? Ooooh hoo hoo hoo!'
      })
    }
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
                <AlertError icon={"fas fa-exclamation"} title={'You angered the Queen'}>
                  <p>
                    {this.state.errorMessage}
                  </p>
                </AlertError>
                : null
            }
            {
              this.state.successMessage !== null ?
                <AlertSuccess icon={"fas fa-check-circle"} title={'Look at you beautiful!'}>
                  <p><em>Do you like it?</em> The Queen bats here eye lashes at you and shoves the item and her cleavage in your face with a big wide grin!</p>
                  <p>{this.state.successMessage}</p>
                  <p className="mt-2">
                    <strong>Note</strong>: If you have the item open in a new tab, refreshing that tab will not show you the new details. We have essentially generated a new item.
                  </p>
                </AlertSuccess>
                : null
            }
            <div className="form-group">
              <label htmlFor="enchantment-valuation" className="mr-2">Select Item</label>
              <select className="form-control mr-2" id="enchantment-valuation" value={this.state.formOptions.selectedSlotId} onChange={this.updateSelectedSlot.bind(this)}>
                <option key={''} value={0}>Please Select</option>
                {this.renderInventorySelectOptions()}
              </select>
            </div>
            <div className={this.state.formOptions.selected_slot_id === 0 ? 'hide' : 'form-group'}>
              <label htmlFor="enchantment-valuation" className="mr-2">Select Affix</label>
              <select className="form-control mr-2" id="enchantment-valuation" value={this.state.formOptions.selectedAffix} onChange={this.updateSelectedAffix.bind(this)}>
                <option key={''} value={0}>Please Select</option>
                {this.renderAffixSelectOptions()}
                <option key={'all-enchantments'} value={'all-enchantments'}>All attached enchantments</option>
              </select>
              <p className="mt-2">
                Selecting All attached enchantments, on a unique where only one of the enchantments is randomly generated will not re roll any non unique enchantments on the item.
                As such, only unique enchantments are listed here.
              </p>
            </div>
            <div className={this.state.formOptions.selected_affix === 0 ? 'hide' : 'form-group'}>
              <label htmlFor="enchantment-valuation" className="mr-2">What to re roll</label>
              <select className="form-control mr-2" id="enchantment-valuation" value={this.state.formOptions.selectedReRollType} onChange={this.updateReRollType.bind(this)}>
                <option key={''} value={''}>Please Select</option>
                {this.renderReRollSelectOptions()}
              </select>
              {
                this.state.selectedActionDescription !== '' ?
                  <p id="type-help" className="mt-2">
                    {this.state.selectedActionDescription}
                  </p>
                : null
              }
            </div>
            <hr />
            <dl>
              <dt>Your Gold Dust:</dt>
              <dd>{this.formatNumber(this.props.currencies.gold_dust)}</dd>
              <dt>Your Shards:</dt>
              <dd>{this.formatNumber(this.props.currencies.shards)}</dd>
              <dt>Cost (Gold Dust)</dt>
              <dd className={this.state.canAfford ? 'text-success' : 'text-danger'}>{this.formatNumber(this.state.formOptions.gold_dust_cost)}</dd>
              <dt>Cost (Shards)</dt>
              <dd className={this.state.canAfford ? 'text-success' : 'text-danger'}>{this.formatNumber(this.state.formOptions.shard_cost)}</dd>
            </dl>
            <hr />
            <button className="btn btn-primary" disabled={!this.canReRoll() || this.state.queenProcessing} onClick={this.reRollEnchantment.bind(this)}>Reroll</button>
          </div>
          <div className="col-md-6">
            <h3 className="tw-font-light">Help</h3>
            <hr />
            <p>
              Here you will select one of your uniques.
            </p>
            <p>
              Next you will select, once you select a unique, one or both of the enchantments. If you select both affixes the cost will double.
              The cost is based on the valuation cost, the more valuable, the more expensive to re roll.
            </p>
            <p>
              Finally, you will select the section of the affix to re-roll, or you can re-roll everything. Each section
              will correspond to one of the tabs you see when looking at the affixes details.
            </p>
            <p>All of this will cost Gold Dust and Shards. The more expensive the item, the more currency you need to move it fro on item to the next.</p>
            <p>
              For more info, please read up on <a href="/information/random-enchants">Uniques (AKA: Random Enchants)</a>.
            </p>
          </div>
        </div>
      </Fragment>
    );
  }
}
