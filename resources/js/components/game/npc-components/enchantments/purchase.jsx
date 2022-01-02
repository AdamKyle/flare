import React, {Fragment} from 'react';
import AlertSuccess from "../../components/base/alert-success";
import AlertError from "../../components/base/alert-error";

export default class Purchase extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      errorMessage: null,
      successMessage: null,
      queenProcessing: false,
      canAfford: false,
      valuation: '',
    }
  }

  purchaseItem() {
    if (this.state.valuation !== '') {
      this.setState({
        errorMessage: null,
        queenProcessing: true,
        successmessage: null,
      }, () => {
        axios.post('/api/character/'+this.props.characterId+'/random-enchant/purchase', {
          type: this.state.valuation
        }).then((result) => {
          this.setState({
            queenProcessing: false,
            successMessage: 'You gained: ' + result.data.item.affix_name
          }, () => {
            if (this.props.hasOwnProperty('fetchUniqueData')) {
              this.props.fetchUniqueData();
            }
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
      });
    } else {
      this.setState({
        errorMessage: 'ooooooh hoo hooo hooo child! You cannot trick me, you must spend your coin on me for me to love you. Do you want me to love you? THEN PICK ONE!'
      });
    }
  }

  handleValuationChange(e) {
    const value = e.target.value;
    let cost    = 0;

    switch (value) {
      case 'basic':
        cost = 10000000000;
        break;
      case 'meduim':
        cost = 50000000000;
        break;
      case 'legendary':
        cost = 100000000000;
        break;
      default:
        throw new Error('Could not determine valuation for queen of hearts.');
    }

    if (cost > this.props.currencies.gold) {
      this.setState({
        errorMessage: 'No no child. You are too poor for such luxuries. Sigh, and all I ever wanted was a rich handsome ..... what ever you are. Please, sigh, pick another one.',
        canAfford: false,
      });
    } else {
      this.setState({
        errorMessage: null,
        valuation: value !== '' ? value : '',
        canAfford: value !== '' ? true : false,
      });
    }
  }

  renderOptions() {
    const options = [];

    for (const key in this.props.uniquesToBuy) {
      const option = this.props.uniquesToBuy[key];

      options.push(
        <option key={key} value={option.type}>{key} Gold, dr: {option.damage_range.join('-')} pts., pr: {option.percentage_range.join('-')}%</option>
      );
    }

    return options;
  }

  formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
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
                <AlertError icon={"fas fa-exclamation"} title={'You angered the queen'}>
                  <p>
                    {this.state.errorMessage}
                  </p>
                </AlertError>
                : null
            }
            {
              this.state.successMessage !== null ?
                <AlertSuccess icon={"fas fa-check-circle"} title={'Look at you beautiful!'}>
                  <p><em>oooooh hooo hooo hooo! Look at you my beautiful child. Look at that big powerful enchanted item! Ooooooh hooo hoo hoo!</em></p>
                  <p>{this.state.successMessage}</p>
                </AlertSuccess>
                : null
            }
            <div className="form-group">
              <label htmlFor="enchantment-valuation" className="mr-2">Valuation of purchase</label>
              <select className="form-control mr-2" id="enchantment-valuation" onChange={this.handleValuationChange.bind(this)}>
                <option key={''} value={''}>Please Select</option>
                {this.renderOptions()}
              </select>
            </div>
            <hr />
            <dl>
              <dt>Your gold:</dt>
              <dd>{this.formatNumber(this.props.currencies.gold)}</dd>
            </dl>
            <hr />
            <button className="btn btn-primary" onClick={() => this.purchaseItem()} disabled={!this.state.canAfford}>Purchase</button>
          </div>
          <div className="col-md-6">
            <h3 className="tw-font-light">Help</h3>
            <hr />
            <p>
              When selecting a valuation, this is what you will pay. All stats are randomly generated. The item she picks will
              never go above 4 billion gold. Both item, amount of affixes (1 or 2) and it's affix stats will all be randomly generated.
            </p>
            <p>
              You will pay the valuation cost, get the item and then you can re-roll it's stats.
            </p>
            <p>
              There are three parts to each valuation: Cost, Damage Range (dr) and Percentage Range (pr). Damage Range (or dr) is how much damage a
              randomly generate affix can do. Weather or not it is stackable/irresistible is up to lady luck her self. <br />
              Percentage Range (or pr) is applied to all aspects of the affix that is a percentage based value, from enemy stat reductions, player stat increases, skills,
              and so on.
            </p>
            <p>
              For more info, please read up on <a href="/information/random-enchants">Uniques (AKA: Random Enchants)</a>.
            </p>
          </div>
        </div>
      </Fragment>
    );
  }
}