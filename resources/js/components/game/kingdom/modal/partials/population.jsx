import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import AlertWarning from "../../../components/base/alert-warning";

export default class Population extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      toPurchase: 0,
      showError: false,
      errorText: null,
      loading: false,
    }
  }

  purchase() {
    this.setState({
      showError: false,
      errorText: null,
      loading: true
    }, () => {
      if (this.state.toPurchase <= 0) {
        return this.setState({
          showError: true,
          errorText: 'Total to purchase cannot be less then or equal to 0.',
          loading: false,
        });
      }

      const characterGold = this.props.characterGold;
      const cost          = 5 * this.state.toPurchase;

      if (characterGold < cost) {
        return this.setState({
          showError: true,
          errorText: 'And you are getting this money from where? The sky! People are not cheap child!',
          loading: false,
        });
      }

      axios.post('/api/kingdoms/purchase-people/' + this.props.kingdomId, {
        amount_to_purchase: this.state.toPurchase
      }).then((result) => {
        const purchased = this.state.toPurchase;

        this.setState({
          toPurchase: 0,
          loading: false
        }, () => {
          this.props.purchasedSuccess(purchased);
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

  updateToBuy(e) {
    let value = parseInt(e.target.value);

    if (value > 2000000000) {
      value = 2000000000
    }

    if (value < 0) {
      value = 0;
    }

    this.setState({
      toPurchase: value
    });
  }

  getPeopleAllowed() {
    return 2000000000 - this.props.currentPopulation;
  }

  render() {
    return (
      <Modal onHide={this.props.close} backdrop="static" keyboard={false} show={this.props.show}>
        <Modal.Header closeButton>
          <Modal.Title>Buy Population</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {
            this.state.showError ?
              <div className="alert alert-danger mb-2 mt-2">
                {this.state.errorText}
              </div>
              : null
          }

          <p>
            <em>
              One should never ask where the people come from. It's a contentious subject even among the scholars of the land. I can see that look of skepticism in your eyes
              child, alas you are here for a reason. Lets do business.
            </em>
          </p>

          <AlertWarning icon={'fas fa-exclamation-triangle'} title={'ATTN!'}>
            <p>
              You may only buy a maximum of 2 billion people at a time. Your current population can never go over 2 billion.
            </p>
            <p>Should you have more people in your kingdom, come the hourly reset, then the maximum you're allowed to have (ie, 1000/100 = 900 more people), The Old Man (Kingdom Holder NPC) will be angry. You
            will see a global message of him stomping around. First he will take gold from your kingdom per person over the max population. If you have 500 people and can only hold 100
            that's 5,000,000 gold (10k Per additional person over the cap). He will first take from the treasury of the afflicted kingdom. If there is not enough gold, he will take it or the rest of the owed amount
            out of your pockets. If you and your kingdom do not have enough gold to pay, you will loose the kingdom.</p>
            <p>If you have gold bars from the Goblin Coin Bank, he will take the bars until the price is paid rounding up. If he were to take 2.5 bars, he will take three.</p>
            <p>Should you still not be able to pay, he will reduce all the buildings, units, everything, to 0. He will then Take the kingdom and finally he will destroy the kingdom, clearing up
            space for a new kingdom. You will see all this as global messages.</p>
          </AlertWarning>

          <dl>
            <dt>Cost Per Person:</dt>
            <dd>5 Gold</dd>
            <dt>Your Gold:</dt>
            <dd>{this.props.characterGold}</dd>
            <dt>Total Cost:</dt>
            <dd>{(this.state.toPurchase * 5).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</dd>
            <dt>Current Population:</dt>
            <dd>{this.props.currentPopulation}</dd>
            <dt>Max Can Buy:</dt>
            <dd>{this.getPeopleAllowed()}</dd>
          </dl>

          <div className="mt-2">
            <div className="form-group">
              <label htmlFor="embezzle-amount">People to buy</label>
              <input
                type="number"
                className="form-control"
                id="embezzle-amount"
                value={this.state.toPurchase}
                onChange={this.updateToBuy.bind(this)}
                max={this.getPeopleAllowed()}
                disabled={this.getPeopleAllowed() <= 0}
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
          <Button variant="primary" onClick={this.purchase.bind(this)}>
            Purchase
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
