import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class Embezzle extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      totalToEmbezzle: 0,
      showError: false,
      errorText: null,
      loading: false,
    }
  }

  embezzle() {
    this.setState({
      showError: false,
      errorText: null,
      loading: true,
    }, () => {
      if (this.state.totalToEmbezzle <= 0) {
        return this.setState({
          showError: true,
          errorText: 'Total to embezzle cannot be less than or equal to 0.',
          loading: false,
        });
      }

      axios.post('/api/kingdoms/embezzle/' + this.props.kingdomId, {
        embezzle_amount: this.state.totalToEmbezzle
      }).then((result) => {
        const amountEmbezzled = this.state.totalToEmbezzle;

        this.setState({
          totalToEmbezzle: 0,
          loading: false,
        }, () => {
          this.props.embezzledSuccess(amountEmbezzled);
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

          if (response.status === 422) {
            return this.setState({
              showError: true,
              errorText: response.data.message,
              loading: false,
            });
          }
        }
      });
    });
  }

  updateEmbezzle(e) {
    this.setState({
      totalToEmbezzle: e.target.value
    });
  }

  disableEmbezzle() {
    if (this.props.morale <= 0.15) {
      return true;
    }

    if (this.props.treasury <= 0) {
      return true;
    }

    return false;
  }

  render() {
    return (
      <Modal onHide={this.props.close} backdrop="static" keyboard={false} show={this.props.show}>
        <Modal.Header closeButton>
          <Modal.Title>Embezzle</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {
            this.state.showError ?
              <div className="alert alert-danger mb-2 mt-2">
                {this.state.errorText}
              </div>
              : null
          }

          <p>Embezzling from your kingdom will reduce the kingdoms morale by 15% regardless of the amount you
            embezzle. This will also decrease the defensive bonus to your kingdom of having large amount of gold in your treasury.</p>
          <p>You cannot embezzle if your kingdoms morale is 15% or lower.</p>
          <div className="mt-2">
            <dl>
              <dt><strong>Total Treasury</strong>:</dt>
              <dd>{this.props.treasury}</dd>
            </dl>
          </div>
          {
            this.props.morale <= 0.15 ?
              <p className="text-danger mt-3 mb-2">Your morale is too low. You cannot embezzle.</p>
              : null
          }
          <div className="mt-2">
            <div className="form-group">
              <label htmlFor="embezzle-amount">Embezzle Amount</label>
              <input
                type="number"
                className="form-control"
                id="embezzle-amount"
                value={this.state.totalToEmbezzle}
                onChange={this.updateEmbezzle.bind(this)}
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
          <Button variant="primary" onClick={this.embezzle.bind(this)} disabled={this.disableEmbezzle()}>
            Embezzle
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
