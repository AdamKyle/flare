import React from 'react';
import Weapons from './components/weapons';
import Armour from './components/armour';
import Artifacts from './components/artifacts';
import Spells from './components/spells';
import Rings from './components/rings';
import Inventory from './components/inventory';

export default class Shop extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      shop: null,
      inventory: null,
      isLoading: true,
    }

    this.inventory = Echo.private('update-shop-inventory-' + this.props.userId);
  }

  componentDidMount() {
    axios.get('/api/shop/' + this.props.characterId)
      .then((result) => {
        this.setState({
          shop: {
            weapons: result.data.weapons,
            armour: result.data.armour,
            artifacts: result.data.artifacts,
            spells: result.data.spells,
            rings:  result.data.rings,
          },
          inventory: result.data.inventory,
          isLoading: false,
        });
      })
      .catch((error) => {
        console.log(error);
      });

    this.inventory.listen('Game.Core.Events.UpdateShopInventoryBroadcastEvent', (event) => {
      console.log(event);
      this.setState({
        inventory: event.inventory,
      });
    });
  }

  render() {
    if (this.state.isLoading) {
      return 'please wait ...';
    }

    return (
      <>
        <h4>Shop</h4>
        <hr />
        <Weapons weapons={this.state.shop.weapons} characterId={this.props.characterId} />
        <Armour armours={this.state.shop.armour} characterId={this.props.characterId} />
        <Artifacts artifacts={this.state.shop.artifacts} characterId={this.props.characterId} />
        <Spells spells={this.state.shop.spells} characterId={this.props.characterId} />
        <Rings rings={this.state.shop.rings} characterId={this.props.characterId} />
        <hr />
        <Inventory inventory={this.state.inventory} characterId={this.props.characterId} />
      </>
    );
  }
}
