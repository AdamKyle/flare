import React from 'react';
import {Button, Modal, Row, Col} from 'react-bootstrap';

export default class ChatMessageActions extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      ban_params: {
        ban_for: '',
        ban_message: 'You have been banned for your message: ' + this.props.message.message,
        user_id: this.props.message.user_id
      },
      silence_params: {
        for: '',
        user_id: this.props.message.user_id,
      },
      showBanMessage: false,
      openSilence: false,
      errorMessage: null
    }
  }

  showBanUser() {
    this.setState({
      errorMessage: null,
      showBanMessage: true,
    });
  }

  showSilenceUser() {
    this.setState({
      errorMessage: null,
      openBanMessage: false,
      openSilence: true,
    });
  }

  banUser() {
    if (this.props.message.character_name === 'Admin') {
      return this.setState({
        errorMessage: 'Cannot ban your self.',
      });
    }

    if (this.state.ban_params.ban_for.trim() === '') {
      return this.setState({
        errorMessage: 'Must select length of ban.',
      });
    }

    if (this.state.ban_params.ban_message.trim() === '') {
      return this.setState({
        errorMessage: 'Must supply a reason why they are being banned.',
      });
    }

    this.setState({
      errorMessage: null,
      loading: true
    }, () => {
      axios.post('/api/admin/ban-user', this.state.ban_params).then((result) => {
        this.setState({
          loading: false,
        }, () => {
          this.props.close();
        });
      }).catch((err) => {
        console.err(err);
      });
    });
  }

  silenceUser() {
    if (this.state.silence_params.for.trim() === '') {
      return this.setState({
        errorMessage: 'Please select how long to silence a user for.'
      });
    }

    this.setState({
      errorMessage: null,
      loading: true,
    }, () => {
      axios.post('/api/admin/silence-user', this.state.silence_params).then((result) => {
        this.setState({
          loading: false,
        }, () => {
          this.props.close();
        })
      }).catch((err) => {
        console.err(err);
      });
    })
  }

  forceNameChange() {
    this.setState({
      errorMessage: null,
      loading: true,
    }, () => {
      axios.post('/api/admin/force-name-change/' + this.props.message.user_id).then((result) => {
        this.setState({
          loading: false,
        }, () => {
          this.props.close();
        })
      }).catch((err) => {
        console.err(err);
      });
    });
  }

  banMessage(e) {
    const params = this.state.ban_params;

    params.ban_message = e.target.value;

    this.setState({
      ban_params: params,
    });
  }

  banFor(e) {
    const params = this.state.ban_params;

    params.ban_for = e.target.value;

    this.setState({
      ban_params: params,
    });
  }

  silenceFor(e) {
    const params = this.state.silence_params;

    params.for = e.target.value;

    this.setState({
      silence_params: params,
    });
  }

  render() {
    return (
      <Modal
        show={this.props.show}
        onHide={this.props.close}
        aria-labelledby="kingdom-management-modal"
        backdrop="static"
      >
        <Modal.Header closeButton>
          <Modal.Title id="kingdom-management-modal">
            Actions For User
          </Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {
            this.state.errorMessage !== null ?
              <div className="alert alert-danger">
                {this.state.errorMessage}
              </div> : null
          }
          <Row>
            <Col xs={12}>
              <div className="text-center">
                <button className="btn btn-danger mr-2" onClick={this.showBanUser.bind(this)}>Ban User</button>
                <button className="btn btn-danger mr-2" onClick={this.showSilenceUser.bind(this)}>Silence</button>
                <button className="btn btn-primary" onClick={this.forceNameChange.bind(this)}>Force Name Change</button>
              </div>
            </Col>
          </Row>

          <div className="mt-3 mb-3">
            {
              this.state.showBanMessage ?
                <div>
                  <hr />
                  <div className="form-group row">
                    <label className="col-sm-3 col-form-label">Ban Message</label>
                    <div className="col-sm-9">
                      <textarea className="form-control" value={this.state.ban_params.ban_message} onChange={this.banMessage.bind(this)}></textarea>
                    </div>
                  </div>
                  <div className="form-group row">
                    <label htmlFor="inputPassword" className="col-sm-3 col-form-label">Ban For</label>
                    <div className="col-sm-9">
                      <select className="form-control" value={this.state.ban_params.ban_for} onChange={this.banFor.bind(this)}>
                        <option value="">Please select</option>
                        <option value="one-day">1 Day</option>
                        <option value="one-week">1 Week</option>
                        <option value="perm">For Ever</option>
                      </select>
                    </div>
                  </div>
                  <div className="form-group row">
                    <div className="col-sm-12">
                      <button className="btn btn-primary float-right" onClick={this.banUser.bind(this)}>Ban User</button>
                    </div>
                  </div>
                </div>
                : null
            }

            {
              this.state.openSilence ?
                <>
                  <hr />
                  <div className="form-group row">
                    <label htmlFor="inputPassword" className="col-sm-3 col-form-label">Silence For</label>
                    <div className="col-sm-9">
                      <select className="form-control" value={this.state.silence_params.for} onChange={this.silenceFor.bind(this)}>
                        <option value="">Please select</option>
                        <option value="10">10 Minutes</option>
                        <option value="30">30 Minutes</option>
                        <option value="60">60 Minutes</option>
                      </select>
                    </div>
                  </div>
                  <div className="form-group row">
                    <div className="col-sm-12">
                      <button className="btn btn-primary float-right" onClick={this.silenceUser.bind(this)}>Silence User</button>
                    </div>
                  </div>
                </> : null
            }

            {
              this.state.loading ?
                <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
                  <div className="progress-bar progress-bar-striped indeterminate">
                  </div>
                </div>
                : null
            }
          </div>
          <Modal.Footer>
            <Button variant="danger" onClick={this.props.close}>
              Cancel
            </Button>
          </Modal.Footer>
        </Modal.Body>
      </Modal>
    );
  }
}
