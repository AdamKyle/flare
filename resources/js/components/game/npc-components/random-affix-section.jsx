import React, {Fragment} from 'react';
import Card from "../components/templates/card";
import {Tab, Tabs} from "react-bootstrap";
import Purchase from "./enchantments/purchase";
import ReRoll from "./enchantments/re-roll";
import Move from "./enchantments/move";

export default class RandomAffixSection extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: true,
      queenProcessing: false,
      uniquesOnHand: [],
      nonUniques: [],
      uniquesToBuy: [],
      characterCurrencies: {
        gold: 0,
        gold_dust: 0,
        shards: 0,
      },
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

  updateCurrencies(goldDust, shards, gold) {
    const currencies = JSON.parse(JSON.stringify(this.state.characterCurrencies));

    currencies.shards = typeof shards !== 'undefined' ? shards : this.state.characterCurrencies.shards;
    currencies.gold_dust = typeof goldDust !== 'undefined' ? goldDust : this.state.characterCurrencies.gold_dust;
    currencies.gold = typeof gold !== 'undefined' ? gold : this.state.characterCurrencies.gold;

    this.setState({
      characterCurrencies: currencies,
    });
  }

  fetchUniqueData() {
    const self = this;

    axios.get('/api/character/'+this.props.characterId+'/inventory/uniques').then((result) => {
      this.setState({
        uniquesOnHand: result.data.slots,
        nonUniques: result.data.non_unique_slots,
        uniquesToBuy: result.data.valuations,
        hasGold: result.data.has_gold,
        hasInventorySpace: result.data.has_inventory_room,
        characterCurrencies: {
          gold: result.data.character_gold,
          gold_dust: result.data.character_gold_dust,
          shards: result.data.character_shards,
        },
        loading: false,
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.data === 429) {
          return self.props.openTimeOutModal();
        }
      }
    });
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
          : null
        }
        {
          this.state.uniquesOnHand.length === 0 ?
            <Fragment>
              <div className="text-center">
                <p>
                  <em>ooooh hoo hoo hoo! Are you hiding all your uniques? They have to be in your inventory silly.</em>
                </p>
                <p>
                  <em>Maybe you want to buy one from me instead handsome .... What are you again? Oooh hoo hoo hoo!!!</em>
                </p>
              </div>
              <hr />
              <Purchase uniquesToBuy={this.state.uniquesToBuy}
                        currencies={this.state.characterCurrencies}
                        fetchUniqueData={this.fetchUniqueData.bind(this)}
                        characterId={this.props.characterId}
              />
            </Fragment>
          :
            <Fragment>
              <Tabs defaultActiveKey="purchase-unique" id="queen-of-hearts">
                <Tab eventKey='purchase-unique' title='Purchase'>
                  <div className="mt-2">
                    <Purchase uniquesToBuy={this.state.uniquesToBuy}
                              currencies={this.state.characterCurrencies}
                              characterId={this.props.characterId}
                              updateCurrencies={this.updateCurrencies.bind(this)}
                    />
                  </div>
                </Tab>
                <Tab eventKey='re-roll-unique' title='Re-roll'>
                  <div className="mt-2">
                    <ReRoll uniquesOnHand={this.state.uniquesOnHand}
                            currencies={this.state.characterCurrencies}
                            characterId={this.props.characterId}
                            updateCurrencies={this.updateCurrencies.bind(this)}
                    />
                  </div>
                </Tab>
                <Tab eventKey='move-unique' title='Move Unique'>
                  <div className="mt-2">
                    <Move uniquesOnHand={this.state.uniquesOnHand}
                          currencies={this.state.characterCurrencies}
                          characterId={this.props.characterId}
                          updateCurrencies={this.updateCurrencies.bind(this)}
                          fetchUniqueData={this.fetchUniqueData.bind(this)}
                          nonUniques={this.state.nonUniques}
                    />
                  </div>
                </Tab>
              </Tabs>
            </Fragment>
        }
      </Card>
    );
  }
}
