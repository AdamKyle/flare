import React from 'react';
import {Alert} from 'react-bootstrap'

export default class Weapons extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      weaponId: 0,
      message: null,
      errorMessage: null,
    }
  }

  renderWeaponsList() {
    return this.props.weapons.map((item) => {
      return <option key={"weapon-" + item.id} value={item.id}>{item.name} cost: {item.cost}</option>
    });
  }

  selectWeapon(event) {
    this.setState({
      weaponId: event.target.value !== 0 ? event.target.value : 0,
    });
  }

  buyWeapon() {
    this.setState({
      errorMessage: null,
      message: null,
    });

    axios.post('/api/shop/buy/' + this.props.characterId, {
      item_id: this.state.weaponId,
    }).then((result) => {
      this.setState({
        message: result.data.message,
        weapondId: 0,
      });
    }).catch((error) => {
      const response = error.response;

      this.setState({
        errorMessage: response.data.message,
        weapondId: 0,
      });
    });
  }

  render() {
    return(
      <div className="form-row">
        {this.state.errorMessage !== null
         ?
         <Alert variant="danger" onClose={() => this.setState({errorMessage: null})} dismissible>
           {this.state.errorMessage}
         </Alert>
         : null
        }

        {this.state.message !== null
         ?
         <Alert variant="success" onClose={() => this.setState({message: null})} dismissible>
           {this.state.message}
         </Alert>
         : null
        }

        <div className="form-group col-md-8">
          <select value={this.state.weaponId} className="form-control" id="weapons" onChange={this.selectWeapon.bind(this)}>
            <option value={0}>---Weapons---</option>
            {this.renderWeaponsList()}
          </select>
        </div>
        <div className="form-group col-md-4">
          <button type="submit" className="btn btn-primary btn-sm mb-2 ml-2" disabled={this.state.weaponId === 0} onClick={this.buyWeapon.bind(this)}>Buy</button>
        </div>
      </div>
    );
  }

}
