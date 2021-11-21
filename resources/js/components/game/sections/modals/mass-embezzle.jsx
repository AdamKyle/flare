import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import AlertError from "../../components/base/alert-error";
import AlertInfo from "../../components/base/alert-info";

export default class MassEmbezzle extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      embezzleAmount: 0,
      errorMessage: null,
    }
  }

  setEmbezzleAmount(event) {

    let value = parseInt(event.target.value) || 0;

    if (value > 2000000000) {
      value = 2000000000;
    }

    if (value < 0) {
      value = 0;
    }

    this.setState({
      embezzleAmount: value,
    });
  }

  embezzle() {
    if (this.state.embezzleAmount <= 0 || this.state.embezzleAmount > 2000000000) {
      this.setState({
        errorMessage: 'Amount must be between 1 and 2 Billion gold.'
      });

      return;
    }

    this.setState({
      errorMessage: null,
    });

    axios.post('/api/kingdoms/mass-embezzle/' + this.props.characterId, {
      embezzel_amount: this.state.embezzleAmount
    }).then((result) => {
      this.props.close();
    }).catch((error) => {
      this.setState({
        errorMessage: error.response.data.errors
      });
    });
  }

  render() {

    return (
      <>
        <Modal show={this.props.show} keyboard={false} backdrop="static">
          <Modal.Header>
            <Modal.Title>Mass Embezzle</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {
              this.state.errorMessage !== null ?
                <AlertError icon={"fas fa-exclamation"} title={'Oops!'}>
                  <p>{this.state.errorMessage}</p>
                </AlertError>
              : null
            }
            <p>
              Here you can enter an amount to be embezzled from all your kingdom on the current plane.
              Ths will not embezzle from other planes, you must be on that plane to mass embezzle from them.
            </p>
            <p>
              If at any time the amount of gold to be given to you would take you above cap, the embezzling will stop.
            </p>
            <p>
              Through out the process pay attention to chat, this will tell you what, where and how much gold was taken out.
              This process can take longer the more kingdoms you have.
            </p>
            <p>
              Embezzling will run in the background so you are free to logout, move around, fight monsters, traverse and so on. But you cannot
              start a mass embezzling request while one is running and they cannot be canceled.
            </p>
            <AlertInfo icon={'fas fa-question-circle'} title={"ATTN!"}>
              <p>
                Pay attention to chat, to see morale reductions, embezzlement amounts and status messages. There are 4 stages:
              </p>
              <ul>
                <li>
                  <strong>Stopping</strong>: We have to stop, you are gold capped or would be.
                </li>
                <li>
                  <strong>Embezzled</strong>: We have embezzled the money requested. You will loose 15% morale.
                </li>
                <li>
                  <strong>Skipping</strong>: We have to skip as the morale is too low.
                </li>
                <li>
                  <strong>Finished</strong>: We are done.
                </li>
              </ul>
            </AlertInfo>
            <div className="form-group row">
              <label className="col-md-4 col-form-label text-md-right">How much to embezzle?</label>
              <div className="col-md-6">
                <input className="form-control" type="number" min={0} max={2000000000} value={this.state.embezzleAmount} onChange={this.setEmbezzleAmount.bind(this)}/>
              </div>
            </div>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="primary" onClick={this.embezzle.bind(this)}>
              Embezzle
            </Button>
            <Button variant="danger" onClick={() => this.props.close()}>
              Cancel
            </Button>
          </Modal.Footer>
        </Modal>
      </>
    );
  }
}
