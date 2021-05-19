import React from 'react';

export default class UpgradeSection extends React.Component {

  constructor(props) {
    super(props);
  }

  getIncrease(type) {
    const building = this.props.building;

    if (building.hasOwnProperty('future_' + type + '_increase')) {
      return building['future_' + type + '_increase'];
    }

    return 0;
  }

  render() {
    return (
      <div className="row mt-2">
        <div className="col-md-6">
          <dl>
            <dt><strong>Wood Gain/hr</strong>:</dt>
            <dd className="text-success">{this.getIncrease('wood')}</dd>
            <dt><strong>Clay Gain/hr</strong>:</dt>
            <dd className="text-success">{this.getIncrease('clay')}</dd>
            <dt><strong>Stone Gain/hr</strong>:</dt>
            <dd className="text-success">{this.getIncrease('stone')}</dd>
            <dt><strong>Iron Gain/hr</strong>:</dt>
            <dd className="text-success">{this.getIncrease('iron')}</dd>
            <dt><strong>Population Gain/hr</strong>:</dt>
            <dd className="text-success">{this.getIncrease('population')}</dd>
            {
              this.props.building.is_farm ?
                <>
                  <dt><strong>Population Becomes</strong>:</dt>
                  <dd className="text-success">+100</dd>
                </> : null
            }
          </dl>
        </div>
        <div className="col-md-6">
          <dl>
            <dt><strong>Durability Becomes</strong>:</dt>
            <dd className="text-success">{this.getIncrease('durability')}</dd>
            <dt><strong>Defence Becomes</strong>:</dt>
            <dd className="text-success">{this.getIncrease('defence')}</dd>
          </dl>
        </div>
      </div>
    );
  }
}
