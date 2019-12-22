import React from 'react';
import {Alert} from 'react-bootstrap'

export default class Rings extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      ringId: 0,
      message: null,
      errorMessage: null,
    }
  }

  renderRingsList() {
    return this.props.rings.map((item) => {
      return <option key={"ring-" + item.id} value={item.id}>{item.name} cost: {item.cost}</option>
    });
  }

  selectRing(event) {
    this.setState({
      ringId: event.target.value !== 0 ? event.target.value : 0,
    });
  }

  buyRing() {
    this.setState({
      errorMessage: null,
      message: null,
    });

    axios.post('/api/shop/buy/' + this.props.characterId, {
      item_id: this.state.ringId,
    }).then((result) => {
      this.setState({
        message: result.data.message,
        ringId: 0,
      });
    }).catch((error) => {
      const response = error.response;

      this.setState({
        errorMessage: response.data.message,
        ringId: 0,
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
          <select value={this.state.ringId} className="form-control" id="weapons" onChange={this.selectRing.bind(this)}>
            <option value={0}>---Rings---</option>
            {this.renderRingsList()}
          </select>
        </div>
        <div className="form-group col-md-4">
          <button type="submit" className="btn btn-primary btn-sm mb-2 ml-2" disabled={this.state.ringId === 0} onClick={this.buyRing.bind(this)}>Buy</button>
        </div>
      </div>
    );
  }

}
