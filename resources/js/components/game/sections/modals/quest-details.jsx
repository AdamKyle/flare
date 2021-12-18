import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import LoadingModal from "../../components/loading/loading-modal";
import AttackType from "../../battle/attack/attack-type";

export default class QuestDetails extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    console.log(this.props.quest);
    return (
      <>
        <Modal show={this.props.show} onHide={this.props.questDetailsClose}>
          <Modal.Header closeButton>
            <Modal.Title>{this.props.quest.name}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <p>Content here ...</p>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={this.props.questDetailsClose}>
              Close
            </Button>
          </Modal.Footer>
        </Modal>
      </>
    );
  }
}
