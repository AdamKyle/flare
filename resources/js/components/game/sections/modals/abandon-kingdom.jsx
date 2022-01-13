import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import AlertError from "../../components/base/alert-error";
import AlertWarning from "../../components/base/alert-warning";

export default class AbandonKingdom extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      errorMessage: null,
    }
  }

  abandon() {

    this.setState({
      errorMessage: null,
    });

    axios.post('/api/kingdoms/abandon/' + this.props.kingdom.id, {
      embezzle_amount: this.state.embezzleAmount
    }).then((result) => {
      this.props.close();
    }).catch((error) => {
      this.setState({
        errorMessage: error.response.data.message
      });
    });
  }

  render() {
    return (
      <>
        <Modal show={this.props.show} keyboard={false} backdrop="static">
          <Modal.Header>
            <Modal.Title>Abandon Kingdom - {this.props.kingdom.name}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            {
              this.state.errorMessage !== null ?
                <AlertError icon={"fas fa-exclamation"} title={'Oops!'}>
                  <p>{this.state.errorMessage}</p>
                </AlertError>
              : null
            }
            <p>Are you sure you want to do this?</p>
            <p>There are some consequences for just abandoning your kingdoms.</p>
            <ul>
              <li>Kingdom will loose 100% of it's Treasury.</li>
              <li>Kingdom will loose 75% of it's units and population</li>
              <li>Kingdom buildings will loose 35% of durability</li>
              <li>Kingdom morale will fall to 50%.</li>
              <li><strong>You will not be allowed to settle another kingdom for 30 minutes as punishment.</strong></li>
            </ul>
            <p>
              Characters cannot give kingdoms to another player for the sake of trading gold. Characters must wage war and take the kingdom if they
              want the kingdom.
            </p>
            <p>If your kingdom has Gold Bars, you will not be able to abandon the kingdom, you will be told as such when trying to abandon it.</p>
            <AlertWarning icon={'fas fa-exclamation-triangle'} title={'ATTN! Timeout Stacks!'}>
              <p>If you abandon another kingdom after this one, you will incur another 30 minute timeout <strong>ON TOP</strong> of the one you already have.</p>
              <p>
                For example, lets assume you abandon this kingdom and 15 minutes later, abandon another one.
                The timeout has increased from 15 (because 15 minutes already passed) to 45 (15 + 30 = 45). There is no where in game to see this timer.
                You will be told when you can settle again via chat. Attempting to settle will also tell you how many minutes you have left.
              </p>
            </AlertWarning>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="primary" onClick={this.abandon.bind(this)}>
              Abandon
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
