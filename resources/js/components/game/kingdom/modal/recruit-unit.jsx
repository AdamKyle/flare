import React from 'react';
import {Modal} from 'react-bootstrap';
import UnitData from './partials/unit-data';
import Recruit from './partials/recruit';

export default class RecruitUnit extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      amount: 0,
    }
  }

  updateAmount(amount) {
    this.setState({
      amount: amount,
    });
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
          <div className="mb-3">
            <Recruit
              currentPopulation={this.props.kingdom.current_population}
              showUnitRecruitmentSuccess={this.props.showUnitRecruitmentSuccess}
              unit={this.props.unit} kingdom={this.props.kingdom}
              updateAmount={this.updateAmount.bind(this)}
              updateKingdomData={this.props.updateKingdomData}
              close={this.props.close}
            />
          </div>
          <UnitData unit={this.props.unit} amount={this.state.amount} kingdom={this.props.kingdom}/>
        </Modal.Body>
        <Modal.Footer>
          <button className="btn btn-danger" onClick={this.props.close}>Close</button>
        </Modal.Footer>
      </Modal>
    );
  }
}
