import React, {Fragment} from 'react';
import {Row, Col} from "react-bootstrap";
import TimeOutBar from "../timeout/timeout-bar";
import Select from "react-select";

export default class SmithyWorkBench extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      canCraft: true,
      isDead: false,
      isAdventuring: false,
      selectedItem: null,
      selectedAlchemyItem: null,
      totalGoldDustCost: 0,
      items: null,
      alchemyItems: null,
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
    return this.props.characterGoldDust < this.state.totalGoldDustCost
  }

  cannotApplyReason() {
    if (this.canNotApplyHolyOil()) {
      return 'Not enough Gold Dust.'
    }

    return ''
  }

  applyOil() {

  }

  render() {
    if (this.state.loading) {
      return <p className="mt-2">One moment ...</p>
    }

    return  (
      <Row>
        <Col xs={12} sm={12} md={8} lg={8} xl={6}>
          <div className="mt-2">
            <Select
              isClearable
              onChange={this.setItemToApply.bind(this)}
              onInputChange={this.handleInputChange}
              options={this.buildItemList(this.state.items)}
              isDisabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring}
            />
          </div>
          <div className="mt-2">
            <Select
              isClearable
              onChange={this.setAlchemyItemToUse.bind(this)}
              onInputChange={this.handleInputChange}
              options={this.buildItemList(this.state.alchemyItems)}
              isDisabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring}
            />
          </div>
          <div className="mt-4">
            <dl>
              <dt>Gold Dust Cost:</dt>
              <dd>{this.state.totalGoldDustCost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</dd>
              <dt>Max Holy Stacks:</dt>
              <dd>{this.state.selectedItem !== null ? this.state.selectedItem.holy_stacks : 0}</dd>
              <dt>Holy Stacks Left:</dt>
              <dd>0</dd>
              {
                this.cannotApplyReason() !== '' ?
                  <Fragment>
                    <dt>Cannot Apply Because:</dt>
                    <dd className="tw-text-red-600">{this.cannotApplyReason()}</dd>
                  </Fragment>
                : null
              }
            </dl>
          </div>
        </Col>
        <Col xs={12} sm={12} md={8} lg={8} xl={6}>
          <Row>
            <Col xs={3}>
              <button className="btn btn-primary mt-2"
                      type="button"
                      disabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring || this.canNotApplyHolyOil()}
                      onClick={this.applyOil.bind(this)}
              >
                Apply Oil!
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
    );
  }

}
