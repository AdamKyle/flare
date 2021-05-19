import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class Embezzel extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      totalToEmbezzel: 0,
      showError: false,
      errorText: null,
    }
  }

  embezzel() {
    this.setState({
      showError: false,
      errorText: null,
    }, () => {
      if (this.state.totalToEmbezzel <= 0) {
        return this.setState({
          showError: true,
          errorText: 'Total to embezzle cannot be less then or equal to 0.'
        });
      }

      axios.post('/api/kingdoms/embezel/' + this.props.kingdomId, {
        embezzel_amount: this.state.totalToEmbezzel
      }).then((result) => {
        const amountEmbzzeled = this.state.totalToEmbezzel;

        this.setState({
          totalToEmbezzel: 0,
        }, () => {
          this.props.embezzeledSuccess(amountEmbzzeled);
          this.props.close();
        });
      }).catch((error) => {
        console.error(error);
      });
    });
  }

  updateEmbezzel(e) {
    this.setState({
      totalToEmbezzel: e.target.value
    });
  }

  disableEmbezel() {
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

          <p>Embezzling from your kingdom will reduce the kingdoms morale by 5% regardless of the amount you
            embezzle.</p>
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
              <label htmlFor="embezzel-amount">Embezzle Amount</label>
              <input
                type="number"
                className="form-control"
                id="embezzel-amount"
                value={this.state.totalToEmbezzel}
                onChange={this.updateEmbezzel.bind(this)}
              />
            </div>
          </div>
        </Modal.Body>
        <Modal.Footer>
          <Button variant="danger" onClick={this.props.close}>
            Close
          </Button>
          <Button variant="primary" onClick={this.embezzel.bind(this)} disabled={this.disableEmbezel()}>
            Embezzel
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
