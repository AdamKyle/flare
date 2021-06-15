import React from 'react';
import EnemyKingdomModal from "../modals/enemy-kingdom-modal";

export default class EnemyKingdomPin extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      kingdom: null,
      openKingdomModal: false,
    }
  }

  openEnemyKingdomModal(event) {
    const kingdom = this.props.kingdoms.filter(
      l => l.id === parseInt(event.target.getAttribute('data-enemy-kingdom-id'))
    )[0];

    this.setState({
      kingdom: kingdom,
      openKingdomModal: true,
    });
  }

  closeKingdomModal() {
    this.setState({
      kingdom: null,
      openKingdomModal: false,
    });
  }

  renderKingdoms() {
    return this.props.kingdoms.map((kingdom) => {
      let style = {
        top: kingdom.y_position,
        left: kingdom.x_position,
        '--kingdom-color': '#e82b13',
      };

      return (
        <div
          key={Math.random().toString(36).substring(7) + '-' + kingdom.id}
          data-enemy-kingdom-id={kingdom.id}
          className="kingdom-x-pin"
          style={style}
          onClick={this.openEnemyKingdomModal.bind(this)}
        >
        </div>
      );
    });
  }

  render() {
    return (
      <>
        {this.renderKingdoms()}

        {
          this.state.openKingdomModal ?
            <EnemyKingdomModal show={this.state.openKingdomModal} close={this.closeKingdomModal.bind(this)}
                               kingdom={this.state.kingdom}/>
            : null
        }
      </>
    );
  }
}
