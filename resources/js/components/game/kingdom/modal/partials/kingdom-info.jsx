import React from 'react';
import Embezzel from './embezzel';
import {Alert, OverlayTrigger, Tooltip} from 'react-bootstrap';
import Deposit from "./deposit";
import Population from "./population";

const renderDeposit = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    Deposit Gold into kingdom. This will increase your morale by 15%. The more gold you have the higher your defence.
    Having 2 billion gold gives defences 100% extra defence. Treasury is increased per hour based on gold, your Lust for Gold skill
    bonus and the level of your keep. Check help docs, under kingdoms for more info.
  </Tooltip>
);

const renderWithdrawl = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    By removing any amount of gold you will loose 15% morale each time you embezzle. Your kingdom will also loose the added defence
    bonus by the amount of gold you embezzle. Remember having gold increases defence.
  </Tooltip>
);

const renderHelp = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    This comes from the amount of gold in your kingdom divided by the max amount of gold (2 billion) your kingdom can have.
    This defence is also applied, albeit at half, to your kingdom when items are dropped on your kingdom as a percentage towards
    damage reduction.
  </Tooltip>
);

const renderPopulation = (props) => (
  <Tooltip id="button-tooltip" {...props}>
    A kingdom can buy more people. These people will show up instantly. How ever, be cautioned. If you have more people then you are allowed at the
    Hourly reset, your kingdom treasury will loose 10,000 gold PER person over the cap. Buying people is only done to increase your military.
  </Tooltip>
);

export default class KingdomInfo extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      showEmbezzel: false,
      showDeposit: false,
      showSuccess: false,
      successMessage: null,
    }
  }

  getCurrentMorale() {
    return (this.props.kingdom.current_morale * 100).toFixed(2);
  }

  getTreasury() {
    if (this.props.kingdom.treasury === null) {
      return 0;
    }

    return this.props.kingdom.treasury.toLocaleString('en-US', {maximumFractionDigits: 0});
  }

  getResourceIncrease(type) {
    const building = this.props.kingdom.buildings.filter((b) => b[type] !== 0);

    if (_.isEmpty(building)) {
      return 0;
    }

    return building[0][type];
  }

  getTotalMoraleIncreasePerHour() {
    let currentMoraleIncrease = 0;

    const buildings = this.props.kingdom.buildings;

    buildings.forEach((building) => {
      if (building.current_durability !== 0) {
        currentMoraleIncrease += building.morale_increase;
      }
    });

    return (currentMoraleIncrease * 100).toFixed(2);
  }

  getTotalMoraleDecreasePerHour() {
    let currentMoraleDecrease = 0;

    const buildings = this.props.kingdom.buildings;

    buildings.forEach((building) => {
      if (building.current_durability === 0) {
        currentMoraleDecrease += building.morale_decrease;
      }
    });

    return (currentMoraleDecrease * 100).toFixed(2);
  }

  showBuyPeople() {
    this.setState({
      showBuyPeople: true,
    });
  }

  closeBuyPeople() {
    this.setState({
      showBuyPeople: false,
    });
  }

  showEmbezzel() {
    this.setState({
      showEmbezzel: true,
    });
  }

  closeEmbezzel() {
    this.setState({
      showEmbezzel: false,
    });
  }

  embezzeledSuccess(amount) {
    this.setState({
      showSuccess: true,
      successMessage: 'Embezzled ' + amount + ' gold from kingdom. The kingdoms morale has dropped by 15%.',
    });
  }

  showDeposit() {
    this.setState({
      showDeposit: true,
    });
  }

  closeDeposit() {
    this.setState({
      showDeposit: false,
    });
  }

  depositSuccess(amount) {
    this.setState({
      showSuccess: true,
      successMessage: 'Deposited ' + amount + ' gold to the kingdom. The kingdoms morale has increased by 15% and your defence bonus has also been increased.',
    });
  }

  peoplePurchasedSuccess(amount) {
    this.setState({
      showSuccess: true,
      successMessage: 'You dirty, dirty animal. I knew it! Purchased: ' + amount + ' People. Do you feel good about what you just did?',
    })
  }

  closeSuccess() {
    this.setState({
      showSuccess: false,
      successMessage: null
    })
  }

  render() {
    return (
      <>
        {
          this.state.showSuccess ?
            <div className="mb-2 mt-2">
              <Alert variant="success" onClose={this.closeSuccess.bind(this)} dismissible>
                {this.state.successMessage}
              </Alert>
            </div>
            : null
        }
        <div className="row mt-3">
          <div className="col-md-3">
            <dl>
              <dt><strong>Population</strong>:
                <p>
                  <OverlayTrigger
                    placement="bottom"
                    delay={{ show: 250, hide: 400 }}
                    overlay={renderPopulation}
                  >
                    <button className="btn btn-success btn-sm mr-2" onClick={this.showBuyPeople.bind(this)}>
                      <i className="ra ra-gold-bar"></i>
                    </button>

                  </OverlayTrigger>
                </p>
              </dt>
              <dd>{this.props.kingdom.current_population} / {this.props.kingdom.max_population}</dd>
            </dl>
          </div>
          <div className="col-md-3">
            <dl>
              <dt><strong>Morale</strong>:</dt>
              <dd>{this.getCurrentMorale()}%</dd>
            </dl>
          </div>
          <div className="col-md-3">
            <dl>
              <dt>
                <strong>Treasury: </strong>

                <p>
                <OverlayTrigger
                  placement="bottom"
                  delay={{ show: 250, hide: 400 }}
                  overlay={renderDeposit}
                >
                  <button className="btn btn-success btn-sm mr-2" onClick={this.showDeposit.bind(this)}>
                    <i className="ra ra-gold-bar"></i>
                  </button>

                </OverlayTrigger>
                <OverlayTrigger
                  placement="bottom"
                  delay={{ show: 250, hide: 400 }}
                  overlay={renderWithdrawl}
                >
                  <button className="btn btn-danger btn-sm" onClick={this.showEmbezzel.bind(this)}>
                    <i className="ra ra-gold-bar"></i>
                  </button>

                </OverlayTrigger>
                </p>
              </dt>
              <dd>{this.getTreasury()}</dd>
            </dl>
          </div>
          <div className="col-md-3">
            <dl>
              <dt><strong>Location (X/Y)</strong>:</dt>
              <dd>{this.props.kingdom.x_position} / {this.props.kingdom.y_position}</dd>
            </dl>
          </div>
        </div>
        <hr/>
        <div className="row">
          <div className="col-md-3">
            <dl>
              <dt><strong>Wood</strong>:</dt>
              <dd>{this.props.kingdom.current_wood} / {this.props.kingdom.max_wood}</dd>
            </dl>
          </div>
          <div className="col-md-3">
            <dl>
              <dt><strong>Clay</strong>:</dt>
              <dd>{this.props.kingdom.current_clay} / {this.props.kingdom.max_clay}</dd>
            </dl>
          </div>
          <div className="col-md-3">
            <dl>
              <dt><strong>Stone</strong>:</dt>
              <dd>{this.props.kingdom.current_stone} / {this.props.kingdom.max_stone}</dd>
            </dl>
          </div>
          <div className="col-md-3">
            <dl>
              <dt><strong>Iron</strong>:</dt>
              <dd>{this.props.kingdom.current_iron} / {this.props.kingdom.max_iron}</dd>
            </dl>
          </div>
        </div>
        <hr/>
        <div className="row">
          <div className="col-md-3">
            <dl>
              <dt><strong>Wood Increase/hr</strong>:</dt>
              <dd>{this.getResourceIncrease('wood_increase')}</dd>
            </dl>
          </div>
          <div className="col-md-3">
            <dl>
              <dt><strong>Clay Increase/hr</strong>:</dt>
              <dd>{this.getResourceIncrease('clay_increase')}</dd>
            </dl>
          </div>
          <div className="col-md-3">
            <dl>
              <dt><strong>Stone Increase/hr</strong>:</dt>
              <dd>{this.getResourceIncrease('stone_increase')}</dd>
            </dl>
          </div>
          <div className="col-md-3">
            <dl>
              <dt><strong>Iron Increase/hr</strong>:</dt>
              <dd>{this.getResourceIncrease('iron_increase')}</dd>
            </dl>
          </div>
        </div>
        <hr/>
        <div style={{backgroundColor: 'rgb(174 212 234)', padding: '20px'}}>
          <div className="row">
            <div className="col-md-3">
              <dl>
                <dt><strong>Morale Increase/hr</strong>:</dt>
                <dd>{this.getTotalMoraleIncreasePerHour()}%</dd>
              </dl>
            </div>
            <div className="col-md-3">
              <dl>
                <dt><strong>Morale Decrease/hr</strong>:</dt>
                <dd>{this.getTotalMoraleDecreasePerHour()}%</dd>
              </dl>
            </div>
            <div className="col-md-3">
              <dl>
                <dt><strong>Population Increase/hr</strong>:</dt>
                <dd>{this.getResourceIncrease('population_increase')}</dd>
              </dl>
            </div>
            <div className="col-md-3">
              <dl>
                <dt><strong>Current Defence Bonus</strong> <OverlayTrigger
                  placement="bottom"
                  delay={{ show: 250, hide: 400 }}
                  overlay={renderHelp}
                >
                  <i className="far fa-question-circle"></i>

                </OverlayTrigger>:</dt>
                <dd>{(this.props.kingdom.treasury_defence * 100).toFixed(4)}% </dd>
              </dl>
            </div>
          </div>
        </div>
        <Embezzel
          show={this.state.showEmbezzel}
          close={this.closeEmbezzel.bind(this)}
          morale={this.props.kingdom.current_morale}
          treasury={this.props.kingdom.treasury}
          kingdomId={this.props.kingdom.id}
          embezzeledSuccess={this.embezzeledSuccess.bind(this)}
        />
        <Deposit
          show={this.state.showDeposit}
          close={this.closeDeposit.bind(this)}
          morale={this.props.kingdom.current_morale}
          treasury={this.props.kingdom.treasury}
          kingdomId={this.props.kingdom.id}
          depositSuccess={this.depositSuccess.bind(this)}
          characterGold={this.props.characterGold}
        />
        <Population
          show={this.state.showBuyPeople}
          close={this.closeBuyPeople.bind(this)}
          kingdomId={this.props.kingdom.id}
          purchasedSuccess={this.peoplePurchasedSuccess.bind(this)}
          characterGold={this.props.characterGold}
        />
      </>
    )
  }
}
