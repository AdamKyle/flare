import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class KingdomUnitRecall extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      message: null,
    }
  }

  componentDidUpdate() {
    const found = this.props.unitsInMovement.filter((uim) => uim.id === this.props.unitsToRecall.id);

    if (found.length === 0) {
      this.setState({
        loading: false,
        message: null,
      }, () => {
        this.props.close();
      });
    }
  }

  movementType() {
    const unitsInMovement = this.props.unitsToRecall;

    if (unitsInMovement.is_attacking) {
      return 'Attacking';
    }

    if (unitsInMovement.is_returning) {
      return 'Returning';
    }

    if (unitsInMovement.is_recalled) {
      return 'Recalled';
    }

    if (unitsInMovement.is_moving) {
      return 'Moving';
    }
  }

  recall() {
    this.setState({
      loading: true,
    });

    axios.post('/api/recall-units/' + this.props.unitsToRecall.id + '/' + this.props.characterId).then((result) => {
      this.setState({
        loading: false,
      }, (result) => {
        if (result.data.hasOwnProperty('message')) {
          this.setState({
            message: result.data.message,
            loading: false,
          });
        } else {
          this.setState({
            message: null,
            loading: false,
          }, () => {
            this.props.close();
          });
        }
      })
    }).catch((err) => {
      console.error(err);
    });
  }

  render() {
    return (
      <Modal
        show={this.props.show}
        onHide={this.props.close}
        aria-labelledby="kingdom-management-modal"
        backdrop="static"
      >
        <Modal.Header closeButton>
          <Modal.Title id="kingdom-management-modal">
            Unit Recall
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <dl>
            <dt><strong>From Kingdom</strong>:</dt>
            <dd>{this.props.unitsToRecall.from_kingdom_name}</dd>
            <dt><strong>To Kingdom</strong>:</dt>
            <dd>{this.props.unitsToRecall.to_kingdom_name}</dd>
            <dt><strong>Movement Type</strong>:</dt>
            <dd>{this.movementType()}</dd>
          </dl>
          {
            this.props.unitsToRecall.is_returning ?
              <p className="mt-3">When these units return a log will be generated for you to see the outcome of the attack.</p>
              : null
          }
          <hr />
          <div className="alert alert-warning">
            Canceling unit movement can only be done if they are moving or attacking. You cannot recall if they are returning.
            You also cannot recall if they are about to arrive at their destination.
          </div>
          {
            this.state.loading ?
              <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
            : null
          }
          <Modal.Footer>
            <Button variant="danger" onClick={this.props.close}>
              Cancel
            </Button>
            <Button variant="success" disabled={this.props.unitsToRecall.is_returning || this.props.unitsToRecall.is_recalled} onClick={this.recall.bind(this)}>
              Recall
            </Button>
          </Modal.Footer>
        </Modal.Body>
      </Modal>
    );
  }
}
