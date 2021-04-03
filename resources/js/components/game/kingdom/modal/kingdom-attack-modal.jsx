import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import KingdomSelection from './partials/attack-sections/kingdom-selection';
import UnitSelection from './partials/attack-sections/unit-selection';

export default class KingdomAttackModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      currentStep: 0,
      finalStep: 1,
      steps: [
        'Select Kingdoms',
        'Send Units'
      ],
      kingdoms: [],
      selectedKingdomData: [],
      unitsToSend: {},
      enableNext: false,
      enableAttack: false,
      loading: false,
    }
  }

  renderSteps() {
    return this.state.steps.map((step, index) => {
      const className = "circle " + (index === this.state.currentStep ? "active" : "");

      return (
        <li key={"step_" + index}>
          <div>
                        <span className={className}>
                            {index + 1}
                        </span>
            {step}
          </div>
        </li>
      );
    });
  }

  enableNext(bool) {
    this.setState({
      enableNext: bool
    });
  }

  enableAttack(bool) {
    this.setState({
      enableAttack: bool,
    });
  }

  setKingdoms(value) {
    this.setState({
      kingdoms: value
    });
  }

  setUnitsToSendValue(unitsToSend) {
    this.setState({
      unitsToSend: unitsToSend,
    });
  }

  next() {
    if (this.state.currentStep === 0) {
      this.setState({
        loading: true,
      }, () => {
        this.getKingdomsUnits();
      });
    }
  }

  attack() {
    const unitsToSend = _.cloneDeep(this.state.unitsToSend);
    const defenderId = this.props.kingdomToAttack.id;

    this.setState({
      loading: true,
    }, () => {
      this.attackKingdom(defenderId, unitsToSend);
    })
  }

  previous() {
    this.setState({
      currentStep: this.state.currentStep - 1,
    });
  }

  attackKingdom(defenderId, unitsToSend) {
    axios.post('/api/kingdoms/' + this.props.characterId + '/attack', {
      defender_id: defenderId,
      units_to_send: unitsToSend,
    }).then((result) => {
      console.log(result);
    }).catch((err) => {
      console.error(err);

      this.props.close();
    });
  }

  getKingdomsUnits() {
    axios.post('/api/kingdoms/' + this.props.characterId + '/attack/selection', {
      selected_kingdoms: this.state.kingdoms
    }).then((result) => {
      this.setState({
        loading: false,
        currentStep: this.state.currentStep + 1,
        selectedKingdomData: result.data,
      })
    }).catch((error) => {
      console.error(error);

      this.props.close();
    });
  }

  isLastStep() {
    return this.state.currentStep === this.state.finalStep;
  }

  render() {
    return (
      <Modal
        show={this.props.show}
        onHide={this.props.close}
        backdrop="static"
        size="lg"
        dialogClassName="large-modal"
      >
        <Modal.Header closeButton>
          <Modal.Title>Attack Kingdom</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <div className="form-wizard">
            <div className="steps clearfix">
              <ul>
                {this.renderSteps()}
              </ul>
            </div>
          </div>
          {
            this.state.loading ?
              <div className="progress loading-progress" style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
              : null
          }
          {
            this.state.currentStep === 0 ?
              <KingdomSelection
                kingdoms={this.props.kingdoms}
                enableNext={this.enableNext.bind(this)}
                setKingdoms={this.setKingdoms.bind(this)}
              />
              : null
          }
          {
            this.state.currentStep === 1 ?
              <UnitSelection
                selectedKingdomData={this.state.selectedKingdomData}
                defendingKingdom={this.props.kingdomToAttack}
                enableAttack={this.enableAttack.bind(this)}
                setUnitsToSendValue={this.setUnitsToSendValue.bind(this)}
              />
              : null
          }
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={this.props.close}>
            Close
          </Button>
          {
            this.state.currentStep !== 0 ?
              <Button variant="primary" onClick={this.previous.bind(this)}>
                Previous
              </Button>
              : null
          }
          {
            this.isLastStep() ?
              <Button variant="success" onClick={this.attack.bind(this)} disabled={!this.state.enableAttack}>
                Attack
              </Button>
              :
              <Button variant="primary" onClick={this.next.bind(this)} disabled={!this.state.enableNext}>
                Next
              </Button>
          }

        </Modal.Footer>
      </Modal>
    )
  }
}
