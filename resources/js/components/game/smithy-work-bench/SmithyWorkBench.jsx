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
      isCraftingFalse: false,
      selectedItem: null,
      selectedAlchemyItem: null,
      errorMessage: null,
      totalGoldDustCost: 0,
      items: [],
      alchemyItems: [],
      timeRemaining: 0,
    }
  }

  componentDidMount() {
    axios.get('/api/character/'+this.props.characterId+'/inventory/smiths-workbench').then((result) => {
      this.setState({
        loading: false,
        items: result.data.items,
        alchemyItems: result.data.alchemy,
      })
      console.log(result);
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
  }

  buildItemList(items) {
    if (items !== null) {
      return items.map((item) => {
        return {
          value: item.item.id,
          label: item.item.name
        }
      });
    }
  }

  setItemToApply(newValue) {
    const foundSlot    = this.state.items.filter((i) => i.item.id === newValue.value);

    if (foundSlot.length > 0) {
      this.setState({
        selectedItem: foundSlot[0].item,
      }, () => {
        this.calculateCosts();
      });
    }
  }

  setAlchemyItemToUse(newValue) {
    const foundSlot    = this.state.alchemyItems.filter((i) => i.item.id === newValue.value);

    if (foundSlot.length > 0) {
      this.setState({
        selectedAlchemyItem: foundSlot[0].item,
      }, () => {
        this.calculateCosts();
      });
    }
  }

  calculateCosts() {
    if (this.state.selectedItem === null) {
      return this.setState({
        totalGoldDustCost: 0,
      });
    }

    let goldDustCost = this.state.selectedItem.holy_stacks * 10000;

    if (this.state.selectedAlchemyItem !== null) {

      goldDustCost *= this.state.selectedAlchemyItem.holy_level;
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
      return 'You must select an item.'
    }

    if (this.state.selectedAlchemyItem === null) {
      return 'You must select an alchemical item.';
    }

    return ''
  }

  applyOil() {
    this.setState({
      isCraftingFalse: true
    });

    axios.post('/api/character/'+ this.props.characterId +'/smithy-workbench/apply', {
      item_id: this.state.selectedItem.id,
      alchemy_item_id: this.state.selectedAlchemyItem.id,
      gold_dust_cost: this.state.totalGoldDustCost,
    }).then((result) => {
      this.setState({
        isCrafting: false,
        items: result.data.items,
        alchemyItems: result.data.alchemy,
      });
    }).catch((err) => {
      this.setState({
        isCraftingFalse: false
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
              <Select
                isClearable
                onChange={this.setItemToApply.bind(this)}
                onInputChange={this.handleInputChange}
                options={this.buildItemList(this.state.items)}
                isDisabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring}
              />
            </div>
            <div className="mt-2">
              <label>Alchemical Item:</label>
              <Select
                isClearable
                onChange={this.setAlchemyItemToUse.bind(this)}
                onInputChange={this.handleInputChange}
                options={this.buildItemList(this.state.alchemyItems)}
                isDisabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring || this.state.selectedItem === null}
              />
            </div>
            <div className="mt-4">
              <dl>
                <dt>Gold Dust Cost:</dt>
                <dd>{this.state.totalGoldDustCost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</dd>
                <dt>Max Holy Stacks:</dt>
                <dd>{this.state.selectedItem !== null ? this.state.selectedItem.holy_stacks : 0}</dd>
                <dt>Holy Stacks Left:</dt>
                <dd>{this.state.selectedItem !== null ? this.state.selectedItem.holy_stacks - this.state.selectedItem.holy_stacks_applied : 0}</dd>
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
                    <dd>{LockedLocationType.getEffect(this.state.selectedAlchemyItem.holy_level).stat_bonus_increase}</dd>
                    <dt>Devouring Darkness Increase:</dt>
                    <dd>{LockedLocationType.getEffect(this.state.selectedAlchemyItem.holy_level).devouring_darkness_range}</dd>
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
                        disabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring || this.canNotApplyHolyOil() || this.state.isCraftingFalse}
                        onClick={this.applyOil.bind(this)}
                >
                  Apply Oil! {this.state.isCraftingFalse ? <i className="fas fa-spinner fa-spin"></i> : null}
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
