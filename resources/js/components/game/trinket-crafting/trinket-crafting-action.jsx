import React, {Fragment} from "react";
import Select from "react-select";
import {Col, Row} from "react-bootstrap";
import TimeOutBar from "../timeout/timeout-bar";

export default class TrinketCraftingAction extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      canCraft: true,
      isDead: this.props.isDead,
      trinket_id: null,
      itemsToCraft: [],
      timeRemaining: 0,
    }
  }

  componentDidMount() {
    axios.get('/api/trinket-crafting/' + this.props.characterId).then((result) => {
      this.setState({
        itemsToCraft: result.data,
      });
    }).catch((error) => {
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

  buildCraftableItemsOptions() {
    if (this.state.itemsToCraft !== null) {
      return this.state.itemsToCraft.map((item) => {
        return {
          value: item.id,
          label: item.name + ' Cost to craft: ' + item.copper_coin_cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' Copper Coins and ' + item.gold_dust_cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",") + ' Gold Dust'
        }
      });
    }
  }

  setItemToCraft(newValue) {
    this.setState({
      trinket_id: newValue.value,
    });
  }

  craft() {
    axios.post('/api/trinket-crafting/craft/' + this.props.characterId  + '/' + this.state.trinket_id).then((result) => {
      console.log(result.data);
    }).catch((error) => {
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

  render() {
    return (
      <Fragment>
        <Row>
          <Col xs={12} sm={12} md={8} lg={8} xl={6}>
            <div className='mt-2'>
              <Select
                isClearable
                onChange={this.setItemToCraft.bind(this)}
                onInputChange={this.handleInputChange}
                options={this.buildCraftableItemsOptions()}
                isDisabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring}
              />
            </div>
          </Col>
          <Col xs={12} sm={12} md={8} lg={8} xl={6}>
            <Row>
              <Col xs={2}>
                <button className="btn btn-primary mt-2"
                        type="button"
                        disabled={this.state.isDead || !this.state.canCraft || this.props.isAdventuring || this.state.trinket_id === null}
                        onClick={this.craft.bind(this)}
                >
                  Craft!
                </button>
              </Col>
              <Col xs={8}>
                <TimeOutBar
                  cssClass={'trinket-crafting-timeout'}
                  readyCssClass={'trinket-crafting-ready'}
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
