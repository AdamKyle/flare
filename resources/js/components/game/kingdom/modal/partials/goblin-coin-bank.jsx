import React from 'react';
import AlertInfo from "../../../components/base/alert-info";

export default class GoblinCoinBank extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      amountOfBars: 0,
      errorMessage: null,
      successMessage: null,
      maxBars: 0,
    }
  }

  componentDidMount() {

    let amount          = 0;
    const characterGold = parseInt(this.props.characterGold.replace(/,/g, ''));
    const costPerBar    = 2000000000;

    if (characterGold > costPerBar) {
      amount = Math.floor(characterGold / costPerBar);
    }

    this.setState({
      maxBars: amount,
    });
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

  formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  render() {
    return (
      <div className="tw-mt-4">
        <p>
          <em>Welcome child, to the Goblin Coin Bank! Here you can transmute your vast wealth into magically infused gold bars.</em>
          <em>These gold bars, up to a maximum of 1000, will add 0.1% bonus to your Current Defence Bonus (Morale and Defence Tab).</em>
          <em> All it costs is 2 billion gold per bar. Don't worry, we gobbies will keep your gold safe!</em>
        </p>
        <AlertInfo icon={'fas fa-question-circle'} title="Info">
          <p>
            Each gold bar costs 2 billion gold. Your kingdom may hold a total of 1000 (2 trillion gold) bars. Should your kingdom fall you will loose all
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
              <p className="tw-text-red-600">You don't have the gold to purchase any bars</p>
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
            disabled={this.state.maxBars <= 0}
          />
        </div>
      </div>
    )
  }
}