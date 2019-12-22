import React from 'react';
import {Alert} from 'react-bootstrap'

export default class Armour extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      armourId: 0,
      message: null,
      errorMessage: null,
    }
  }

  renderArmourList() {
    return this.props.armours.map((item) => {
      return <option key={"armour-" + item.id} value={item.id}>{item.name} cost: {item.cost}</option>
    });
  }

  selectArmour(event) {
    this.setState({
      armourId: event.target.value !== 0 ? event.target.value : 0,
    });
  }

  buyArmour() {
    this.setState({
      errorMessage: null,
      message: null,
    });

    axios.post('/api/shop/buy/' + this.props.characterId, {
      item_id: this.state.armourId,
    }).then((result) => {
      this.setState({
        message: result.data.message,
        armourId: 0,
      });
    }).catch((error) => {
      const response = error.response;

      this.setState({
        errorMessage: response.data.message,
        armourId: 0,
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
          <select value={this.state.armourId} className="form-control" id="armours" onChange={this.selectArmour.bind(this)}>
            <option value={0}>---Armours---</option>
            {this.renderArmourList()}
          </select>
        </div>
        <div className="form-group col-md-4">
          <button type="submit" className="btn btn-primary btn-sm mb-2 ml-2" disabled={this.state.armourId === 0} onClick={this.buyArmour.bind(this)}>Buy</button>
        </div>
      </div>
    );
  }

}
