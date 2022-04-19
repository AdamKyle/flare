import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class Deposit extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      toDeposit: 0,
      showError: false,
      errorText: null,
      loading: false,
    }
  }

  deposit() {
    this.setState({
      showError: false,
      errorText: null,
      loading: true
    }, () => {
      if (this.state.toDeposit <= 0) {
        return this.setState({
          showError: true,
          errorText: 'Total to deposit cannot be less than or equal to 0.',
          loading: false,
        });
      }

      const newTotal = parseInt(this.props.treasury) + parseInt(this.state.toDeposit);

      if (newTotal > 2000000000) {
        return this.setState({
          showError: true,
          errorText: 'Total to deposit would cause the kingdom treasury to go over the max.',
          loading: false,
        });
      }

      axios.post('/api/kingdoms/deposit/' + this.props.kingdomId, {
        deposit_amount: this.state.toDeposit
      }).then((result) => {
        const deposited = this.state.toDeposit;

        this.setState({
          toDeposit: 0,
          loading: false
        }, () => {
          this.props.depositSuccess(deposited);
          this.props.close();
        });
      }).catch((err) => {
        if (err.hasOwnProperty('response')) {
          const response = err.response;

          if (response.status === 401) {
            location.reload();
          }

          if (response.status === 429) {
            return this.props.openTimeOutModal();
          }
        }
      });
    });
  }

  updateDeposit(e) {
    this.setState({
      toDeposit: e.target.value
    });
  }

  render() {
    return (
      <Modal onHide={this.props.close} backdrop="static" keyboard={false} show={this.props.show}>
        <Modal.Header closeButton>
          <Modal.Title>Deposit</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {
            this.state.showError ?
              <div className="alert alert-danger mb-2 mt-2">
                {this.state.errorText}
              </div>
              : null
          }

          <p>Depositing gold will increase your morale by 15% and your defence bonus. This bonus is the total gold you have divided by two billion.</p>
          <p>Depositing gold will also increase the interest you gain per hour, especially if you train the Lust for Gold Skill and Level the Keep to 30.</p>
          <p>The Interest calculation is: Gold in treasury + Gold in treas. * (Lust for Gold skill bonus + Keep Level / 30) * 100.</p>
          <p>Even a few hundred thousand gold can grow quickly over time.</p>
          <p><strong>You can deposit gold if you have very low morale to increase it at any point, regardless of how low your morale is.</strong></p>
          <div className="mt-2">
            <dl>
              <dt><strong>Total Treasury</strong>:</dt>
              <dd>{this.props.treasury.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</dd>
              <dt><strong>Max you can deposit</strong>:</dt>
              <dd>{(2000000000 - this.props.treasury).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</dd>
              <dt><strong className="text-success">Your Total Gold</strong>:</dt>
              <dd>{this.props.characterGold}</dd>
            </dl>
          </div>
          <div className="mt-2">
            <div className="form-group">
              <label htmlFor="embezzle-amount">Deposit Amount</label>
              <input
                type="number"
                className="form-control"
                id="embezzle-amount"
                value={this.state.toDeposit}
                onChange={this.updateDeposit.bind(this)}
              />
            </div>
          </div>
          {
            this.state.loading ?
              <div className="progress loading-progress kingdom-loading " style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
              : null
          }
        </Modal.Body>
        <Modal.Footer>
          <Button variant="danger" onClick={this.props.close}>
            Close
          </Button>
          <Button variant="primary" onClick={this.deposit.bind(this)}>
            Deposit
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
