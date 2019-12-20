import React from 'react';

export default class Shop extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      shop: null,
      inventory: null,
      isLoading: true,
    }
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
  }

  renderWeaponsList() {
    return this.state.shop.weapons.map((item) => {
      return <option key={"weapon-" + item.id}>{item.name} cost: {item.cost}</option>
    });
  }

  renderArmourList() {
    return this.state.shop.armour.map((item) => {
      return <option key={"armour-" + item.id}>{item.name} cost: {item.cost}</option>
    });
  }

  renderArtifactsList() {
    return this.state.shop.artifacts.map((item) => {
      return <option key={"artifact-" + item.id}>{item.name} cost: {item.cost}</option>
    });
  }

  renderSpellsList() {
    return this.state.shop.spells.map((item) => {
      return <option key={"spell-" + item.id}>{item.name} cost: {item.cost}</option>
    });
  }

  renderRingsList() {
    return this.state.shop.rings.map((item) => {
      return <option key={"ring-" + item.id}>{item.name} cost: {item.cost}</option>
    });
  }

  renderInventoryList() {
    return this.state.inventory.map((slot) => {
      return <option key={"slot-" + slot.id}>{slot.item.name}</option>
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
        <div className="form-row">
          <div className="form-group col-md-8">
            <select className="form-control" id="weapons">
              <option>---Weapons---</option>
              {this.renderWeaponsList()}
            </select>
          </div>
          <div className="form-group col-md-4">
            <button type="submit" className="btn btn-primary btn-sm mb-2 ml-2">Buy</button>
          </div>
        </div>
        <div className="form-row">
          <div className="form-group col-md-8">
            <select className="form-control" id="weapons">
              <option>---Armour---</option>
              {this.renderArmourList()}
            </select>
          </div>
          <div className="form-group col-md-4">
            <button type="submit" className="btn btn-primary btn-sm mb-2 ml-2">Buy</button>
          </div>
        </div>
        <div className="form-row">
          <div className="form-group col-md-8">
            <select className="form-control" id="weapons">
              <option>---Artifacts---</option>
              {this.renderArtifactsList()}
            </select>
          </div>
          <div className="form-group col-md-4">
            <button type="submit" className="btn btn-primary btn-sm mb-2 ml-2">Buy</button>
          </div>
        </div>
        <div className="form-row">
          <div className="form-group col-md-8">
            <select className="form-control" id="weapons">
              <option>---Spells---</option>
              {this.renderSpellsList()}
            </select>
          </div>
          <div className="form-group col-md-4">
            <button type="submit" className="btn btn-primary btn-sm mb-2 ml-2">Buy</button>
          </div>
        </div>
        <div className="form-row">
          <div className="form-group col-md-8">
            <select className="form-control" id="weapons">
              <option>---Rings---</option>
              {this.renderRingsList()}
            </select>
          </div>
          <div className="form-group col-md-4">
            <button type="submit" className="btn btn-primary btn-sm mb-2 ml-2">Buy</button>
          </div>
        </div>
        <hr />
        <div className="form-row">
          <div className="form-group col-md-8">
            <select className="form-control" id="weapons">
              <option>---Inventory---</option>
              {this.renderInventoryList()}
            </select>
          </div>
          <div className="form-group col-md-4">
            <button type="submit" className="btn btn-success btn-sm mb-2 ml-2">Sell</button>
          </div>
        </div>
      </>
    );
  }
}
