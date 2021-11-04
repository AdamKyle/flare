import React from 'react';
import {Modal, Button} from 'react-bootstrap';

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

      const characterGold = parseInt(this.props.characterGold.replace(/,/g, ''));
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
    this.setState({
      toPurchase: e.target.value
    });
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
              It's best if you not ask questions, the morale implications and such. You understand correct? no? You're outraged? Where did you think these
              people were going to come from child! The moon! It doesn't work like that, not here. Not in this time. But be warned child! Be warned!
              <strong> For every additional person you have in your kingdom when the day (hour) is up, you will loose 10,000 gold from your treasury, PER PERSON!</strong>
            </em>
          </p>

          <p>
            <em>
              Oh you think ... "I will just empty my treasury." Don't be foolish child, I will take it out of your gold! Don't got any? Kingdoms mine! Don't mess with me.
              If you can't pay, one way or the other, the kingdom is mine.
            </em>
          </p>

          <p>
            <em>
              Don't look at me like that, you already started your "purchase order" you savage animal. You're just like the rest of the blood thirsty kings and queens.
            </em>
          </p>

          <div className="alert alert-warning">
            The Old Man (Kingdom NPC) is not joking. If you purchase a ton of people and do not use them and cannot pay for the additional people, he <strong>WILL</strong>
            take your kingdom. And not just take your kingdom, reduce everything about it to nothing. You have been warned.
          </div>

          <dl>
            <dt>Cost Per Person:</dt>
            <dd>5 Gold</dd>
            <dt>Your Gold:</dt>
            <dd>{this.props.characterGold}</dd>
            <dt>Total Cost:</dt>
            <dd>{(this.state.toPurchase * 5).toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</dd>
          </dl>

          <div className="mt-2">
            <div className="form-group">
              <label htmlFor="embezzel-amount">People to buy</label>
              <input
                type="number"
                className="form-control"
                id="embezzel-amount"
                value={this.state.toPurchase}
                onChange={this.updateToBuy.bind(this)}
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
