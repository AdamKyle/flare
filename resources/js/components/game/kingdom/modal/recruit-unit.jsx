import React from 'react';
import {Modal} from 'react-bootstrap';
import UnitData from './partials/unit-data';
import Recruit from './partials/recruit';

export default class RecruitUnit extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      amount: 0,
      errorMessage: ''
    }
  }

  componentDidMount() {
    this.cannotRecruit();
  }

  updateAmount(amount) {
    this.setState({
      amount: amount,
    });
  }

  cannotRecruit() {
    const building = this.props.kingdom.buildings.filter((b) => b.name === this.props.unit.recruited_from.name)[0];

    if (building.current_durability === 0) {
      this.setState({
        errorMessage: 'This building needs to be repaired. You cannot recruit units from it till you do.'
      });
    } else {
      this.setState({
        errorMessage: ''
      });
    }
  }

  render() {
    return (
      <Modal
        show={this.props.show}
        onHide={this.props.close}
        aria-labelledby="unit-management-modal"
        dialogClassName="large-modal"
        backdrop="static"
      >
        <Modal.Header closeButton>
          <Modal.Title id="unit-management-modal">
            {this.props.unit.name}
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <p className="mb-3 mt-1">{this.props.unit.description}</p>
          {
            this.state.errorMessage !== '' ?
              <div className="alert alert-danger mt-2 mb-2">
                {this.state.errorMessage}
              </div>
              : null
          }
          <div className="mb-3">
            <div className="row">
              <div className="col-md-6">
                <Recruit
                  currentPopulation={this.props.kingdom.current_population}
                  showUnitRecruitmentSuccess={this.props.showUnitRecruitmentSuccess}
                  unit={this.props.unit}
                  kingdom={this.props.kingdom}
                  updateAmount={this.updateAmount.bind(this)}
                  updateKingdomData={this.props.updateKingdomData}
                  close={this.props.close}
                  openTimeOutModal={this.props.openTimeOutModal.bind(this)}
                  characterGold={this.props.characterGold}
                />
              </div>
              <div className="col-md-6">
                <UnitData
                  unit={this.props.unit}
                  amount={this.state.amount}
                  kingdom={this.props.kingdom}
                />
              </div>
            </div>

          </div>

        </Modal.Body>
        <Modal.Footer>
          <button className="btn btn-danger" onClick={this.props.close}>Close</button>
        </Modal.Footer>
      </Modal>
    );
  }
}
