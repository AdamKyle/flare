import React from 'react';
import {Tabs, Tab} from 'react-bootstrap';
import AlertInfo from "../../../components/base/alert-info";
import AlertError from "../../../components/base/alert-error";
import AlertSuccess from "../../../components/base/alert-success";


export default class GoblinCoinBank extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      amountOfBars: 0,
      amountToTakeOut: 0,
      errorMessage: null,
      successMessage: null,
      successTitle: null,
      maxBars: 0,
      maxWithDraw: 0,
    }
  }

  componentDidMount() {
    this.setState({
      maxBars: this.fetchMaxAmountOfBars(true),
      maxWithDraw: this.fetchMaxWithDraw(),
    });
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    const currentMax         = this.fetchMaxAmountOfBars(true);
    const currentMaxWithDraw = this.fetchMaxWithDraw();

    if (currentMax !== this.state.maxBars) {
      this.setState({
        maxBars: currentMax,
      });
    }

    if (currentMaxWithDraw !== this.state.maxWithDraw) {
      this.setState({
        maxWithDraw: currentMaxWithDraw,
      });
    }

  }

  fetchMaxAmountOfBars(isAtaMaxCheck) {
    let amount          = 0;
    const characterGold = this.props.characterGold;
    const costPerBar    = 2000000000;

    if (this.props.kingdom.gold_bars === 1000 && isAtaMaxCheck) {
      return 0;
    }

    if (characterGold > costPerBar) {
      amount = Math.floor(characterGold / costPerBar);
    }

    if (this.props.kingdom.gold_bars < amount) {
      amount -= this.props.kingdom.gold_bars;
    }

    return amount;
  }

  fetchMaxWithDraw() {
    const characterGold = this.props.characterGold;
    const costPerBar    = 2000000000;
    let maxWithdraw    = 0;

    if (characterGold === 2000000000000) {
      return 0;
    }

    let gold = 2000000000000 - characterGold;

    if (gold < costPerBar) {
      return 0;
    } else if (gold > costPerBar) {
      maxWithdraw = Math.floor(gold/costPerBar);
    }

    if (maxWithdraw < this.props.kingdom.gold_bars) {
      return maxWithdraw
    }

    return this.props.kingdom.gold_bars
  }

  barAmount(e) {
    let amount = parseInt(e.target.value) || 0;

    if (amount >= this.state.maxBars) {
      amount = this.state.maxBars;
    }

    this.setState({
      amountOfBars: amount,
    });
  }

  withdrawAmount(e) {
    let amount = parseInt(e.target.value) || 0;

    if (amount >= this.props.kingdom.gold_bars) {
      amount = this.props.kingdom.gold_bars;
    }

    this.setState({
      amountToTakeOut: amount,
    });
  }

  formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  getGoldBars() {
    if (this.state.amountToTakeOut > 0) {
      return this.props.kingdom.gold_bars - this.state.amountToTakeOut;
    }

    return this.props.kingdom.gold_bars;
  }

  goldIncrease() {
    const characterGold = this.props.characterGold;

    const newGold       = this.state.amountToTakeOut * 2000000000;

    return this.formatNumber(characterGold + newGold);
  }

  purchaseBars() {
    this.setState({
      errorMessage: null,
      successMessage: null,
      successTitle: null,
      loading: true,
    });

    axios.post('/api/kingdoms/purchase-gold-bars/' + this.props.kingdom.id, {
      amount_to_purchase: this.state.amountOfBars
    }).then((result) => {
      this.setState({
        successMessage: result.data.message,
        successTitle: 'Purchased Gold Bars!',
        loading: false,
        amountOfBars: 0,
      });
    }).catch((error) => {
      this.setState({loading: false});

      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal();
        }

        this.setState({
          errorMessage: response.data.message
        });
      }
    });
  }

  withdrawBars() {
    this.setState({
      errorMessage: null,
      successMessage: null,
      successTitle: null,
      loading: true,
    });

    axios.post('/api/kingdoms/withdraw-bars-as-gold/' + this.props.kingdom.id, {
      amount_to_withdraw: this.state.amountToTakeOut
    }).then((result) => {
      this.setState({
        successMessage: result.data.message,
        successTitle: 'Exchanged Gold Bars!',
        loading: false,
        amountToTakeOut: 0,
      });
    }).catch((error) => {
      this.setState({loading: false});

      if (error.hasOwnProperty('response')) {
        const response = error.response;

        if (response.status === 401) {
          location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal();
        }

        this.setState({
          errorMessage: response.data.message
        });
      }
    });
  }

  render() {
    return (
      <div className="tw-mt-4">
        {
          this.state.errorMessage !== null ?
            <AlertError icon={"fas fa-exclamation"} title={'Oops!'}>
              <p>{this.state.errorMessage}</p>
            </AlertError>
            : null
        }

        {
          this.state.successMessage !== null ?
            <AlertSuccess icon={"fas fa-check-circle"} title={this.state.successTitle}>
              <p>{this.state.successMessage}</p>
            </AlertSuccess>
            : null
        }

        <Tabs defaultActiveKey="purchase-coins" id="goblin-coin-tabs">
          <Tab eventKey="purchase-coins" title="Purchase Bars">
            <div className="tw-mt-4">
              <p>
                <em>Welcome child, to the Goblin Coin Bank! Here you can transmute your vast wealth into magically infused gold bars.</em>
                <em>These gold bars, up to a maximum of 1000, will add 0.1% bonus to your Current Defence Bonus (Morale and Defence Tab).</em>
                <em> All it costs is 2 billion gold per bar. Don't worry, we gobbies will keep your gold safe!</em>
              </p>
              <AlertInfo icon={'fas fa-question-circle'} title="Info">
                <p>
                  Each gold bar costs 2 billion gold. Your kingdom may hold a total of 1000 (2 trillion gold) bars. Should your kingdom fall you will lose all
                these bars and the player who takes the kingdom gets your vast wealth!
                </p>
                <p>
                  <strong>Note!</strong>: If you have purchased more people then the maximum allowed for your kingdom and you or the kingdom has no gold, The Old Man
                  (Kingdom Holder NPC) will take gold bars equal to the total cost rounded up. If he would take 2.5 bars, he takes three.
                </p>
              </AlertInfo>

              <div className="tw-mt-4">
                <dl className="tw-mb-2">
                  <dt>Your gold:</dt>
                  <dd>{this.props.characterGold}</dd>
                  <dt>Current Bars:</dt>
                  <dd>{this.formatNumber(this.props.kingdom.gold_bars + this.state.amountOfBars)}</dd>
                  <dt>Bars you can purchase:</dt>
                  <dd>{this.formatNumber(this.state.maxBars)}</dd>
                  <dt>Bars to purchase:</dt>
                  <dd>{this.formatNumber(this.state.amountOfBars)}</dd>
                  <dt>Total cost:</dt>
                  <dd>{this.formatNumber(2000000000 * this.state.amountOfBars)}</dd>
                </dl>
                <p>How many bars would you like to buy?</p>
                {
                  this.state.maxBars <= 0 ?
                    <p className="tw-text-red-600">You cannot purchase anymore gold bars. You are either maxed do not have the gold.</p>
                  : null
                }
                <input
                  className="form-control"
                  name="bars-to-purchase"
                  type="number"
                  min={0}
                  max={this.state.maxBars}
                  value={this.state.amountOfBars}
                  onChange={this.barAmount.bind(this)}
                  disabled={this.state.maxBars <= 0 || this.state.loading}
                />
                {
                  this.state.loading ?
                    <div className="progress loading-progress kingdom-loading " style={{position: 'relative'}}>
                      <div className="progress-bar progress-bar-striped indeterminate">
                      </div>
                    </div>
                    : null
                }
                <button className="btn btn-primary mt-2" onClick={this.purchaseBars.bind(this)} disabled={this.state.loading}>Purchase</button>
              </div>
            </div>
          </Tab>
          <Tab eventKey="withdraw-coins" title="Exchange Bars">
            <div className="tw-mt-4">
              <p>
                <em>Looking to withdraw some of your riches child? Lets see what we can do for you today.</em>
                <em> Jack! Get this leader their gold! How much was that again?</em>
              </p>
              <AlertInfo icon={'fas fa-question-circle'} title="Info">
                <p>
                  Withdrawing gold bars as gold will reduce your kingdom defence by the amount of bars you take out.
                  You also cannot take out more bars then space you have in your coin purse!
                </p>
              </AlertInfo>
              <dl>
                <dt>Current Bars:</dt>
                <dd>{this.getGoldBars()}</dd>
                <dt>Can WithDraw:</dt>
                <dd>{this.state.maxWithDraw}</dd>
                <dt>Your gold:</dt>
                <dd>{this.goldIncrease()}</dd>
              </dl>
              <input
                className="form-control mt-2"
                name="bars-to-withdraw"
                type="number"
                min={0}
                max={this.state.maxWithDraw}
                value={this.state.amountToTakeOut}
                onChange={this.withdrawAmount.bind(this)}
                disabled={this.props.kingdom.gold_bars <= 0 || this.state.loading || this.state.maxWithDraw <= 0}
              />
              {
                this.state.loading ?
                  <div className="progress loading-progress kingdom-loading " style={{position: 'relative'}}>
                    <div className="progress-bar progress-bar-striped indeterminate">
                    </div>
                  </div>
                  : null
              }
              <button className="btn btn-primary mt-2" onClick={this.withdrawBars.bind(this)} disabled={this.state.loading || this.props.kingdom.gold_bars <= 0 || this.state.maxWithDraw <= 0}>Purchase</button>
            </div>
          </Tab>
        </Tabs>
      </div>
    )
  }
}
