import React from 'react';
import { Modal, Button } from 'react-bootstrap';
import Axios from 'axios';

export default class AdventureEmbark extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      adventure: null,
      isLoading: true,
    }
  }

  componentDidMount() {
    this.setState({
      adventure: this.props.adventure,
      isLoading: false,
    });
  }

  embark() {    
    axios.post('/api/character/'+this.props.characterId+'/adventure/' + this.state.adventure.id).then((result) => {
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
            <div className="alert alert-info" role="alert">
              <h4 className="alert-heading">Before you set off.</h4>
              <p>
                Please make sure you have equipped any items you want for this adventure. <br />
                Once started you cannot equip gear, you cannot craft, attack or move until the adventure is done<sup>*</sup>.
              </p>
              <p>
                You are still able to manage your kingdoms. This will be done through a third party NPC called
                an advisor.
              </p>
              <hr />
              <p className="mb-0">Should you need additional help, please consider this resource on <a href="/information/adventure" target="_blank">adventureing</a>.</p>
              <p className="text-muted" style={{fontSize: '12px'}}><sup>*</sup> You are free to logout. Any relevant details will be emailed to you should you have those settings enabled.</p>
            </div>
            <div className="mt-2">
              <span className="text-muted"><strong>Total Levels</strong>: {this.state.adventure.levels}</span>
              <br />
              <span className="text-muted"><strong>Total Time</strong>: {this.state.adventure.levels * this.state.adventure.time_per_level}</span>
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