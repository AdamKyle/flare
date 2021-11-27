import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class TrainPassiveSkillModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      showError: false,
      errorMessage: null,
    }
  }

  trainSkill() {

  }

  render() {
    return (
      <Modal
        show={this.props.open}
        onHide={this.props.close}
        backdrop="static"
      >
        <Modal.Header closeButton>
          <Modal.Title>Training: {this.props.skill.passive_skill.name}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          Content
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={this.props.close}>
            Close
          </Button>
          <Button variant="success" onClick={this.trainSkill.bind(this)}>
            Train Skill
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
