import React from 'react';

export default class KingdomPin extends React.Component {
  constructor(props) {
    super(props);
  }

  renderKingdoms() {
    return this.props.kingdoms.map((kingdom) => {
      let style = {
        top: kingdom.y_position,
        left: kingdom.x_position,
        '--kingdom-color': this.convertToHex(kingdom.color)
      };

      return (
        <div
          key={Math.random().toString(36).substring(7) + '-' + kingdom.id}
          data-kingdom-id={kingdom.id}
          className="kingdom-x-pin"
          style={style}>
        </div>
      );
    });
  }

  convertToHex(rgba) {
    return `#${((1 << 24) + (parseInt(rgba[0]) << 16) + (parseInt(rgba[1]) << 8) + parseInt(rgba[2])).toString(16).slice(1)}`
  }

  render() {
    return (
      <>
        {this.renderKingdoms()}
      </>
    )
  }
}