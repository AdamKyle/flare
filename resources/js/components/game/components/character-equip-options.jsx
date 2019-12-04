import React from 'react';

export default class EquipOptions extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      itemToEquip:   this.props.itemToEquip,
      equippedItems: this.props.equippedItems,
      errorMessage:  null,
    }
  }

  fetchIncrease(item) {
    let allEquippedWeaponsDamage = 0;

    this.state.equippedItems.forEach((equipment) => {
      if (item.base_damage > equipment.item.base_damage) {
        allEquippedWeaponsDamage = item.base_damage;
      }
    });

    return allEquippedWeaponsDamage;
  }

  equip(event) {
    const equipmentPosition = event.target.getAttribute('data-type');

    this.setState({
      errorMessage: null,
    });

    axios.post('/api/equip-item/' + this.state.equippedItems[0].character_id, {
      item_id   : this.state.itemToEquip.id,
      type      : equipmentPosition,
      equip_type: this.state.itemToEquip.type,
    }).then((result) => {
      this.props.callHome(result.data.message);
    }).catch((error) => {
      this.setState({
        errorMessage: error.response.data.message
      });
    });
  }

  fetchEquippedItems() {
    return this.state.equippedItems.map((equipment) => {
      return (
        <div className="card">
          <div className="card-header">
            {equipment.item.name}
          </div>
          <div className="card-body">
            <dl>
              <dt>Base Damage:</dt>
              <dd>{equipment.item.base_damage}</dd>
            </dl>
            <dl>
              <dt>Type:</dt>
              <dd>{equipment.item.type}</dd>
            </dl>
            <dl>
              <dt>Equip Slot:</dt>
              <dd>{equipment.type}</dd>
            </dl>
          </div>
        </div>
      );
    })
  }

  renderButtons(item) {
    switch(item.type) {
      case 'weapon':
        return (
          <>
            <button className="btn btn-primary" onClick={this.equip.bind(this)} data-type="left-hand">Left Hand</button>
            <button className="btn btn-primary ml-2" onClick={this.equip.bind(this)} data-type="right-hand">Right Hand</button>
          </>
        );
      default:
        return <button className="btn btn-primary" onClick={this.equip.bind(this)} data-type={item.type}>Confirm</button>
    }
  }

  render() {
    const item = this.state.itemToEquip;
    return (
      <>
        {this.state.errorMessage !== null
         ?
         <div className="row mb-2">
            <div className="col-md-12">
              <div className="alert alert-danger">{this.state.errorMessage}</div>
            </div>
         </div>
         : null
        }
        <div className="row">
          <div className="col-md-6">
            <div className="card">
              <div className="card-header">
                {item.name}
              </div>
              <div className="card-body">
                <dl>
                  <dt>Base Damage:</dt>
                  <dd>{item.base_damage}</dd>
                </dl>
                <dl>
                  <dt>Type:</dt>
                  <dd>{item.type}</dd>
                </dl>
                <hr />
                <dl>
                  <dt>Increases attack by:</dt>
                  <dd>{'+' + this.fetchIncrease(item)}</dd>
                </dl>
              </div>
            </div>
          </div>
          <div className="col-md-6">
            {this.fetchEquippedItems()}
          </div>
        </div>
        <hr />

        <div className="row">
          <div className="col-md-12">
            {this.renderButtons(item)}
          </div>
        </div>
      </>
    );
  }
}
