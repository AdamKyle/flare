import React        from 'react';
import KingdomModal from '../modals/kingdom-modal';

export default class KingdomPin extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      kingdom: null,
      openKingdomModal: false,
    }
  }

  openKingdomModal(event) {
    const kingdom = this.props.kingdoms.filter(
      l => l.id === parseInt(event.target.getAttribute('data-kingdom-id'))
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
        '--kingdom-color': this.convertToHex(kingdom.color)
      };

      return (
        <div
          key={Math.random().toString(36).substring(7) + '-' + kingdom.id}
          data-kingdom-id={kingdom.id}
          className="kingdom-x-pin"
          style={style}
          onClick={this.openKingdomModal.bind(this)}
        >
        </div>
      );
    });
  }

  convertToHex(rgba) {
    if (Array.isArray(rgba)) {
      return `#${((1 << 24) + (parseInt(rgba[0]) << 16) + (parseInt(rgba[1]) << 8) + parseInt(rgba[2])).toString(16).slice(1)}`
    } else {
      return rgba; // already a hex ...
    }
  }

  render() {
    return (
      <>
        {this.renderKingdoms()}

        { 
          this.state.openKingdomModal ?
            <KingdomModal 
              kingdom={this.state.kingdom} 
              show={this.state.openKingdomModal} 
              close={this.closeKingdomModal.bind(this)} 
              characterId={this.props.characterId} 
              disableMapButtons={this.props.disableMapButtons}
            />
          : null
        }
      </>
    )
  }
}