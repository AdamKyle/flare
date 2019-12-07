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
    let increasesDamageBy = 0;
    let replacesWeapon    = 'None';

    this.state.equippedItems.forEach((equipment) => {

      const equippedItemDamage = this.fetchItemDamage(equipment.item);
      const itemDamage         = this.fetchItemDamage(item)

      if (itemDamage > equippedItemDamage) {
        increasesDamageBy = Math.abs(equippedItemDamage - itemDamage);
        replacesWeapon    = this.determineItemName(equipment.item);
      }
    });

    return {
      increasesDamageBy: increasesDamageBy,
      replacesWeapon:    replacesWeapon,
    };
  }

  fetchItemDamage(item) {
    let damage = item.base_damage;

    if (item.artifact_property !== null) {
      damage += item.artifact_property.base_damage_mod;
    }

    if (item.item_affixes.length > 0) {
      item.item_affixes.forEach((affix) => {
        damage += affix.base_damage_mod;
      });
    }

    return damage;
  }

  determineItemName(item) {
    let name = item.name;

    if (item.item_affixes.length > 0) {
      item.item_affixes.forEach((affix) => {
        if (affix.type === 'suffix') {
          name = name + ' *' + affix.name + '*';
        }

        if (affix.type === 'prefix') {
          name = '*' + affix.name + '* ' + name;
        }
      });
    }

    return name;
  }

  equip(event) {
    const equipmentPosition = event.target.getAttribute('data-type');

    this.setState({
      errorMessage: null,
    });

    axios.post('/api/equip-item/' + this.props.characterId, {
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
        <div className="card mb-2" key={equipment.id}>
          <div className="card-header">
            {this.determineItemName(equipment.item)}
          </div>
          <div className="card-body">
            <dl>
              <dt>Damage:</dt>
              <dd>{this.fetchItemDamage(equipment.item)}</dd>
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

  renderAffixes(item) {
    return item.item_affixes.map((affix) => {
      return (
        <div key ={affix.id}>
          <dl>
            <dt>Name:</dt>
            <dd>{affix.name}</dd>
          </dl>
          <dl>
            <dt>Base Damage Mod:</dt>
            <dd>{'+' + affix.base_damage_mod}</dd>
          </dl>
          <div className="mt-2 mb-2 text-center"><i>{item.artifact_property.description}</i></div>
        </div>
      )
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
                {item.artifact_property !== null
                 ?
                  <>
                   <h5>Artifact Details</h5>
                   <dl>
                     <dt>Name:</dt>
                     <dd>{item.artifact_property.name}</dd>
                   </dl>
                   <dl>
                     <dt>Base Damage Mod:</dt>
                     <dd>{'+' + item.artifact_property.base_damage_mod}</dd>
                   </dl>
                   <div className="mt-2 mb-2 text-center"><i>{item.artifact_property.description}</i></div>
                   <hr />
                  </>
                 : null
                }
                {item.item_affixes.length > 0
                 ?
                  <>
                   <h5>Item Affixes</h5>
                   {this.renderAffixes(item)}
                   <hr />
                  </>
                 : null
                }
                <dl>
                  <dt>Increases attack by:</dt>
                  <dd>{'+' + this.fetchIncrease(item).increasesDamageBy}</dd>
                </dl>
                <dl>
                  <dt>Replaces weapon:</dt>
                  <dd>{this.fetchIncrease(item).replacesWeapon}</dd>
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
