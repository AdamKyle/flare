import React, {Fragment} from 'react';
import Card from "../components/templates/card";
import AlertError from "../components/base/alert-error";
import AlertSuccess from "../components/base/alert-success";
import {Tab, Tabs} from "react-bootstrap";

export default class RandomAffixSection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      queenProcessing: false,
      uniquesOnHand: [],
      uniquesToBuy: [],
      valuation: '',
      errorMessage: null,
      successMessage: null,
      hasGold: true,
      hasInventorySpace: true,
    }
  }

  componentDidMount() {
    this.fetchUniqueData();
  }

  fetchUniqueData() {
    axios.get('/api/character/'+this.props.characterId+'/inventory/uniques').then((result) => {
      this.setState({
        uniquesOnHand: result.data.slots,
        uniquesToBuy: result.data.valuations,
        hasGold: result.data.has_gold,
        hasInventorySpace: result.data.has_inventory_room,
      }, () => {
        this.setState({loading: false});
      })
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.data === 429) {
          return this.props.openTimeOutModal();
        }
      }
    });
  }

  purchaseItem(refetchUniqueData) {
    if (this.state.valuation !== '') {
      this.setState({
        errorMessage: null,
        queenProcessing: true,
      }, () => {
        axios.post('/api/character/'+this.props.characterId+'/random-enchant/purchase', {
          type: this.state.valuation
        }).then((result) => {
          this.setState({
            queenProcessing: false,
            successMessage: 'You gained: ' + result.data.item.affix_name
          }, () => {
            if (refetchUniqueData) {
              this.fetchUniqueData();
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

    this.setState({
      valuation: value !== '' ? value : '',
    });
  }

  renderOptions() {
    const options = [];

    for (const key in this.state.uniquesToBuy) {
      const option = this.state.uniquesToBuy[key];

      options.push(
        <option key={key} value={option.type}>{key} Gold, dr: {option.damage_range.join('-')} pts., pr: {option.percentage_range.join('-')}%</option>
      );
    }

    return options;
  }


  renderPurchase(refecth) {
    return (
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
          <div className="form-inline">
            <div className="form-group">
              <label htmlFor="enchantment-valuation" className="mr-2">Valuation of purchase</label>
              <select className="form-control mr-2" id="enchantment-valuation" onChange={this.handleValuationChange.bind(this)}>
                <option key={''} value={''}>Please Select</option>
                {this.renderOptions()}
              </select>
            </div>
            <button className="btn btn-primary" onClick={() => this.purchaseItem(refecth)}>Purchase</button>
          </div>
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
    )
  }

  renderReRoll() {
    return (
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
                <p><em>ooooh hoo hoo hoo!! Look at what I made for you child. I am Queen after all! Oooooh hoo hoo hoo hoo!!!</em></p>
                <p>{this.state.successMessage}</p>
              </AlertSuccess>
              : null
          }
          <div className="form-group">
            <label htmlFor="enchantment-valuation" className="mr-2">Select Item</label>
            <select className="form-control mr-2" id="enchantment-valuation">
              <option key={''} value={''}>Please Select</option>
            </select>
          </div>
          <div className="form-group">
            <label htmlFor="enchantment-valuation" className="mr-2">Select Affix</label>
            <select className="form-control mr-2" id="enchantment-valuation">
              <option key={''} value={''}>Please Select</option>
            </select>
          </div>
          <div className="form-group">
            <label htmlFor="enchantment-valuation" className="mr-2">What to reroll</label>
            <select className="form-control mr-2" id="enchantment-valuation">
              <option key={''} value={''}>Please Select</option>
            </select>
          </div>
          <hr />
          <dl>
            <dt>Your Gold Dust:</dt>
            <dd>100000</dd>
            <dt>Your Shards:</dt>
            <dd>10000</dd>
            <dt>Cost (Gold Dust)</dt>
            <dd>10000</dd>
            <dt>Cost (Shards)</dt>
            <dd>10000</dd>
          </dl>
          <hr />
          <button className="btn btn-primary">Reroll</button>
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
            Finally, you will select the section of the affix to re-roll, or you can re-roll everything. Each section
            will correspond to one of the tabs you see when looking at the affixes details.
          </p>
          <p>All of this will cost Gold Dust and Shards. The more expensive the item, the more currency you need to move it fro on item to the next.</p>
          <p>
            For more info, please read up on <a href="/information/random-enchants">Uniques (AKA: Random Enchants)</a>.
          </p>
        </div>
      </div>
    );
  }

  renderMove() {
    return (
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
                <p><em>Oooooh hoo hooo hoo! It is .... DONE!!!! Child! I have done everything you asked of me .... *wink wink* .... ooooh hoo hoo hoo!</em></p>
                <p>{this.state.successMessage}</p>
              </AlertSuccess>
              : null
          }
          <div className="form-group">
            <label htmlFor="enchantment-valuation" className="mr-2">Select Item</label>
            <select className="form-control mr-2" id="enchantment-valuation">
              <option key={''} value={''}>Please Select</option>
            </select>
          </div>
          <div className="form-group">
            <label htmlFor="enchantment-valuation" className="mr-2">Select Affix</label>
            <select className="form-control mr-2" id="enchantment-valuation">
              <option key={''} value={''}>Please Select</option>
            </select>
          </div>
          <div className="form-group">
            <label htmlFor="enchantment-valuation" className="mr-2">Select item to move to</label>
            <select className="form-control mr-2" id="enchantment-valuation">
              <option key={''} value={''}>Please Select</option>
            </select>
          </div>
          <hr />
          <dl>
            <dt>Your Gold Dust:</dt>
            <dd>100000</dd>
            <dt>Your Shards:</dt>
            <dd>10000</dd>
            <dt>Cost (Gold Dust)</dt>
            <dd>10000</dd>
            <dt>Cost (Shards)</dt>
            <dd>10000</dd>
          </dl>
          <hr />
          <button className="btn btn-primary">Move</button>
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
            Finally select an item to move one or both enchants to. This will <strong>replace</strong> and existing enchants.
            You cannot move crafted enchants, only unique (randomly generated) enchants. You can move them as many times as you can afford.
          </p>
          <p>All of this will cost Gold Dust and Shards. The more expensive the item, the more currency you need.</p>
          <p><strong>Rolls are random. She could make it wore, she could make it better. It's all RNG.</strong></p>
          <p>
            For more info, please read up on <a href="/information/random-enchants">Uniques (AKA: Random Enchants)</a>.
          </p>
        </div>
      </div>
    );
  }

  render() {
    return (
      <Card
        OtherCss="p-3"
        cardTitle="Queen of Hearts"
        close={this.props.closeComponent}
      >
        {
          this.state.loading ?
            <Fragment>
              <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
            </Fragment>
          : this.state.uniquesOnHand.length <= 0 ?
            <Fragment>
              <div className="text-center mt-4">
                <p><em>"Oh child! You don't have anything for me to play with. You so greatly displease me."</em></p>
                <p><em>"Maybe you should buy something from me child. Ooooooh hoo hoo hoo! I would very much like that!"</em></p>
              </div>
              <hr />
              {this.renderPurchase(true)}
            </Fragment>
          :
            <Fragment>
              <Tabs defaultActiveKey="purchase-unique" id="queen-of-hearts">
                <Tab eventKey='purchase-unique' title='Purchase'>
                  <div className="mt-2">
                    {this.renderPurchase(false)}
                  </div>
                </Tab>
                <Tab eventKey='re-roll-unique' title='Re-roll'>
                  <div className="mt-2">
                    {this.renderReRoll()}
                  </div>
                </Tab>
                <Tab eventKey='move-unique' title='Move Unique'>
                  <div className="mt-2">
                    {this.renderMove()}
                  </div>
                </Tab>
              </Tabs>
            </Fragment>
        }
        {
          this.state.queenProcessing ?
            <Fragment>
              <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
            </Fragment>
          : null
        }
      </Card>
    );
  }
}
