import React from 'react';
import { Modal, Button } from 'react-bootstrap';
import Axios from 'axios';

export default class AdventureEmbark extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      adventure: null,
      isLoading: true,
      levelsToComplete: '',
      errorMessage: null,
    }
  }

  componentDidMount() {
    this.setState({
      adventure: this.props.adventure,
      isLoading: false,
    });
  }

  handleLevelSelect(event) {
    this.setState({
      levelsToComplete: event.target.value,
      errorMessage: null,
    });
  }

  buildOptions() {
    const levels = this.state.adventure.levels;

    if (levels % 10 === 0) {
      return <option value="10">10 levels at a time</option>
    } else if (levels % 5 === 0) {
      return <option value="5">5 levels at a time</option>
    }

    return null;
  }

  embark() {
    if (this.state.levelsToComplete === '') {
      return this.setState({errorMessage: 'Cannot embark when you have not selected how many levels at a time.'})
    }
    
    axios.post('/api/character/'+this.props.characterId+'/adventure/' + this.state.adventure.id, {
      levels_at_a_time: this.state.levelsToComplete
    }).then((result) => {
      this.props.updateMessage(result.data.message);
      this.props.updateCharacterAdventures(result.data.adventure_completed_at);
      this.props.embarkClose();
    }).catch((error) => {
      this.setState({
        errorMessage: 'Invalid input. Please try again.'
      });
    });
  }

  render() {
    if (this.state.isLoading) {
      return(
        <>
          <Modal show={this.props.show} onHide={this.props.embarkClose}>
            <Modal.Header closeButton>
              <Modal.Title>Loading ....</Modal.Title>
            </Modal.Header>
            <Modal.Body>
              <p>
                Please wait ....
              </p>
            </Modal.Body>
            <Modal.Footer>
              <Button variant="secondary" onClick={this.props.embarkClose}>
                Close
              </Button>
              <Button variant="primary" onClick={this.props.embarkClose}>
                Save Changes
              </Button>
            </Modal.Footer>
          </Modal>
        </>
      );
    }

    return (
      <>
        <Modal show={this.props.show} onHide={this.props.embarkClose}>
          <Modal.Header closeButton>
            <Modal.Title>{this.state.adventure.name}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {this.state.errorMessage !== null ? <div className="alert alert-danger mb-2">{this.state.errorMessage}</div> : null}

            <div className="alert alert-warning">
              <p>Please make sure you have equipped any items you want for this adventure.
              Once started you cannot equip gear, you cannot craft, attack or move until the adventure is done.</p>
              <p>You can still manage your kingdoms while your character is adventuring.</p>
            </div>
            <div className="row">
              <div className="col-md-12">
                <div className="form-group">
                  <label htmlFor="adventure-levels-to-complete">How many levels at a time:</label>
                  <select className="form-control" id="adventure-levels-to-complete" name="levels_to_complete" value={this.state.levelsToComplete} onChange={this.handleLevelSelect.bind(this)}>
                    <option value="">Please select</option>
                    {this.buildOptions()}
                    <option value="all">All At Once</option>
                  </select>
                  <small id="adventure-levels-to-complete-help" className="form-text text-muted">There are a total of: {this.state.adventure.levels} Levels.</small>
                </div>
              </div>
            </div>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="danger" onClick={this.props.embarkClose}>
              Cancel
            </Button>
            <Button variant="primary" onClick={this.embark.bind(this)}>
              Embark
            </Button>
          </Modal.Footer>
        </Modal>
      </>
    );
  }
} 