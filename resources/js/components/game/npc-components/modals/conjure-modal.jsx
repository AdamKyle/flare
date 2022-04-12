import React from 'react';
import {Modal, ModalDialog} from 'react-bootstrap';
import {capitalize} from 'lodash';

export default class ConjureModal extends React.Component {

  constructor(props) {
    super(props)

    this.state = {
      loading: false,
    }
  }

  buttonDisabled() {
    return this.props.celestial.gold_cost > this.props.gold || this.props.celestial.gold_dust_cost > this.props.goldDust;
  }

  getErrorMessage() {
    if (this.props.celestial.gold_cost > this.props.gold && this.props.celestial.gold_dust_cost > this.props.goldDust) {
      return 'You do not have either the gold or the gold dust.';
    }

    if (this.props.celestial.gold_dust_cost > this.props.goldDust) {
      return 'You do not have the gold dust.';
    }

    if (this.props.celestial.gold_cost > this.props.gold) {
      return 'You do not have the gold.';
    }
  }

  conjure() {
    this.setState({
      loading: true,
    }, () => {
      axios.post('/api/conjure/' + this.props.characterId, {
        monster_id: this.props.celestial.id,
        type: this.props.type,
      }).then((result) => {
        this.setState({
          loading: false
        }, () => {
          this.props.close()
          this.props.closeComponent();
        });
      }).catch((err) => {
        this.setState({
          loading: false
        }, () => {
          if (err.hasOwnProperty('response')) {
            const response = err.response;

            if (response.status === 401) {
              return loadtion.reload();
            }

            if (response.status === 429) {
              return this.props.openTimeOutModal();
            }
          }
        });
      });
    });
  }

  render() {
    return (
      <Modal
        show={this.props.show}
        onHide={this.props.close}
        centered
      >
        <Modal.Header closeButton>
          <Modal.Title id="building-management-modal">
            {capitalize(this.props.type)} Conjuration
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          <h3>Are you sure?</h3>
          <hr />
          { this.buttonDisabled() ?
              <div className="alert alert-danger">
                {this.getErrorMessage()}
              </div>
            : null
          }
          <dl>
            <dt>Gold Cost:</dt>
            <dd>{this.props.celestial.gold_cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</dd>
            <dt>Gold Dust Cost:</dt>
            <dd>{this.props.celestial.gold_dust_cost.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")}</dd>
          </dl>
          <hr />
          {
            this.props.type === 'public' ?
              <p>
                This creature will be conjured publicly, meaning a global message will go out. This also means that
                any player can come and attack the creature. Which could mean, <strong>you might not be the one that kills the creature</strong>.
                Which also means: <strong>You might not get the reward you want.</strong>
              </p>
            :
              <p>
                This creature will be conjured privately. This means a global message will still go out, but <strong>without</strong> the location
                data. You may tell other players where you are, thus they can come and join. However, <strong>you might not be the one that kills the creature</strong>.
                Which also means: <strong>You might not get the reward you want.</strong>
              </p>
          }
          {
            this.state.loading ?
              <div className="progress mb-2 mt-2" style={{position: 'relative', height: '5px'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
            :
              null
          }
        </Modal.Body>
        <Modal.Footer>
          <button className="btn btn-danger" onClick={this.props.close}>Cancel</button>
          <button className="btn btn-success" disabled={this.buttonDisabled()} onClick={this.conjure.bind(this)}>Conjure!</button>
        </Modal.Footer>
      </Modal>
    );
  }
}
