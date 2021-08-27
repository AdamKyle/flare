import React from 'react';
import {Modal, Button, Alert} from 'react-bootstrap';
import KingdomSelection from './partials/attack-sections/kingdom-selection';
import UnitSelection from './partials/attack-sections/unit-selection';
import LoadingModal from "../../components/loading/loading-modal";
import ItemSelection from "./partials/attack-sections/item-selection";

export default class KingdomAttackModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      currentStep: 0,
      finalStep: 2,
      steps: [
        'Select Kingdoms',
        'Send Units'
      ],
      kingdoms: [],
      selectedKingdomData: [],
      attackingKingdoms: [],
      items: [],
      unitsToSend: {},
      enableNext: false,
      enableAttack: false,
      fetchingAttackData: true,
      showItemDroppedMessage: false,
      loading: false,
    }
  }

  componentDidMount() {
    axios.get('/api/kingdoms/'+this.props.characterId+'/kingdoms-with-units').then((result) => {
      this.setState({
        fetchingAttackData: false,
        kingdoms: result.data.kingdoms,
        items: result.data.items,
      }, () => {
        const steps = this.state.steps;

        if (this.state.items.length > 0) {
          steps.unshift('Use Items');
        }

        this.setState({
          steps: steps,
          currentStep: 0,
          finalStep: steps.length === 3 ? 2 : 1,
        });
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload()
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal()
        }
      }
    });
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
      selectedKingdomData: value
    });
  }

  setUnitsToSendValue(unitsToSend) {
    this.setState({
      unitsToSend: unitsToSend,
    });
  }

  next() {
    if (this.state.currentStep !== (this.state.steps.length === 3 ? 2 : 1)) {
      this.setState({
        currentStep: this.state.currentStep + 1,
      }, () => {
        if (this.state.steps.length === 3 && this.state.currentStep === 2) {
          this.getKingdomsUnits();
        } else if (this.state.steps.length === 2 && this.state.currentStep === 1) {
          this.getKingdomsUnits();
        }
      });
    }
  }

  previous() {
    this.setState({
      currentStep: this.state.currentStep - 1,
    });
  }

  isLastStep() {
    return this.state.currentStep === this.state.finalStep;
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

  attackKingdom(defenderId, unitsToSend) {
    axios.post('/api/kingdoms/' + this.props.characterId + '/attack', {
      defender_id: defenderId,
      units_to_send: unitsToSend,
    }).then((result) => {
      this.setState({
        kingdoms: [],
        selectedKingdomData: [],
        attackingKingdoms: [],
        unitsToSend: {},
      }, () => {
        this.props.close();
      });
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal();
        }
      }

      this.props.close();
    });
  }

  updateItems(items) {
    this.setState({
      items: items,
      showItemDroppedMessage: true,
    }, () => {
      const steps = this.state.steps;

      if (items.length === 0) {
        steps.shift()

        this.setState({
          steps: steps,
          currentStep: 0,
          finalStep: 1,
        });
      }
    });
  }

  showItemDropped() {
    this.setState({
      showItemDroppedMessage: !this.state.showItemDroppedMessage
    });
  }

  getKingdomsUnits() {
    this.setState({loading: true});

    axios.post('/api/kingdoms/' + this.props.characterId + '/attack/selection', {
      selected_kingdoms: this.state.selectedKingdomData
    }).then((result) => {
      this.setState({
        loading: false,
        attackingKingdoms: result.data,
      })
    }).catch((err) => {
      if (err.hasOwnProperty('response')) {
        const response = err.response;

        if (response.status === 401) {
          return location.reload();
        }

        if (response.status === 429) {
          return this.props.openTimeOutModal();
        }
      }

      this.props.close();
    });
  }

  render() {
    if (this.state.fetchingAttackData) {
      return (
        <LoadingModal
          loadingText="Fetching Kingdom Attack Data ..."
          show={this.props.show}
          close={this.props.close}
        />
      );
    }

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
            this.state.showItemDroppedMessage ?
              <Alert variant="success" onClose={this.showItemDropped.bind(this)} dismissible>
                <Alert.Heading>Bombs away!</Alert.Heading>
                <p>
                  You dropped items on the kingdom doing devastating damage!
                  You can continue to drop more items, or select a kingdom if you have not used all your items.
                </p>
              </Alert>
            :
              null
          }
          { this.state.steps.length === 3 && this.state.currentStep === 0 ?
              this.state.items.length > 0 ?
                <ItemSelection
                  items={this.state.items}
                  enableNext={this.enableNext.bind(this)}
                  openTimeOutModal={this.props.openTimeOutModal}
                  close={this.props.close}
                  characterId={this.props.characterId}
                  defenderId={this.props.kingdomToAttack.id}
                  updateItems={this.updateItems.bind(this)}
                />
              : <>Test</>
            : null
          }
          {
            this.state.steps.length === 3 && this.state.currentStep === 1 || this.state.steps.length === 2 && this.state.currentStep === 0 ?
              <KingdomSelection
                kingdoms={this.state.kingdoms}
                enableNext={this.enableNext.bind(this)}
                setKingdoms={this.setKingdoms.bind(this)}
              />
              : null
          }
          {
            (this.state.steps.length === 3 && this.state.currentStep === 2 || this.state.steps.length === 2 && this.state.currentStep === 1) && this.state.attackingKingdoms.length > 0 ?
              <UnitSelection
                attackingKingdoms={this.state.attackingKingdoms}
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
