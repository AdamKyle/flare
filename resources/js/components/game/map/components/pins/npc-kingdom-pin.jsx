import React from 'react';

export default class NpcKingdomPin extends React.Component {
  constructor(props) {
    super(props);
  }

  renderKingdoms() {
    return this.props.npcKingdoms.map((kingdom) => {
      let style = {
        top: kingdom.y_position,
        left: kingdom.x_position,
        '--kingdom-color': '#e3d60a'
      };

      return (
        <div
          key={Math.random().toString(36).substring(7) + '-' + kingdom.id}
          data-kingdom-id={kingdom.id}
          className="kingdom-x-pin"
          style={style}
        >
        </div>
      );
    });
  }

  render() {
    return (
      <>
        {this.renderKingdoms()}
      </>
    )
  }
}
