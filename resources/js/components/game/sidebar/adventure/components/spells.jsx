import React from 'react';
import {Alert} from 'react-bootstrap'

export default class Spells extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      spellId: 0,
      message: null,
      errorMessage: null,
    }
  }

  renderSpellsList() {
    return this.props.spells.map((item) => {
      return <option key={"spell-" + item.id} value={item.id}>{item.name} cost: {item.cost}</option>
    });
  }

  selectSpell(event) {
    this.setState({
      spellId: event.target.value !== 0 ? event.target.value : 0,
    });
  }

  buySpell() {
    this.setState({
      errorMessage: null,
      message: null,
    });

    axios.post('/api/shop/buy/' + this.props.characterId, {
      item_id: this.state.spellId,
    }).then((result) => {
      this.setState({
        message: result.data.message,
        spellId: 0,
      });
    }).catch((error) => {
      const response = error.response;

      this.setState({
        errorMessage: response.data.message,
        spellId: 0,
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
          <select value={this.state.spellId} className="form-control" id="weapons" onChange={this.selectSpell.bind(this)}>
            <option value={0}>---Spells---</option>
            {this.renderSpellsList()}
          </select>
        </div>
        <div className="form-group col-md-4">
          <button type="submit" className="btn btn-primary btn-sm mb-2 ml-2" disabled={this.state.spellId === 0} onClick={this.buySpell.bind(this)}>Buy</button>
        </div>
      </div>
    );
  }

}
