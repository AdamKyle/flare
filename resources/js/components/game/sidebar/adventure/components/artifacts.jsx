import React from 'react';
import {Alert} from 'react-bootstrap'

export default class Artifacts extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      artifactId: 0,
      message: null,
      errorMessage: null,
    }
  }

  renderArtifactsList() {
    return this.props.artifacts.map((item) => {
      return <option key={"artifact-" + item.id} value={item.id}>{item.name} cost: {item.cost}</option>
    });
  }

  selectArtifact(event) {
    this.setState({
      artifactId: event.target.value !== 0 ? event.target.value : 0,
    });
  }

  buyArtifact() {
    this.setState({
      errorMessage: null,
      message: null,
    });

    axios.post('/api/shop/buy/' + this.props.characterId, {
      item_id: this.state.artifactId,
    }).then((result) => {
      this.setState({
        message: result.data.message,
        artifactId: 0,
      });
    }).catch((error) => {
      const response = error.response;

      this.setState({
        errorMessage: response.data.message,
        artifactId: 0,
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
          <select value={this.state.artifactId} className="form-control" id="artifacts" onChange={this.selectArtifact.bind(this)}>
            <option value={0}>---Artifacts---</option>
            {this.renderArtifactsList()}
          </select>
        </div>
        <div className="form-group col-md-4">
          <button type="submit" className="btn btn-primary btn-sm mb-2 ml-2" disabled={this.state.artifactId === 0} onClick={this.buyArtifact.bind(this)}>Buy</button>
        </div>
      </div>
    );
  }

}
