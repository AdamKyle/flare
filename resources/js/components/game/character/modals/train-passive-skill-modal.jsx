import React from 'react';
import {Modal, Button} from 'react-bootstrap';
import AlertInfo from "../../components/base/alert-info";
import AlertError from "../../components/base/alert-error";

export default class TrainPassiveSkillModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      showError: false,
      errorMessage: null,
    }
  }

  trainSkill() {
    this.setState({
      loading: true,
      showError: false,
      errorMessage: null,
    }, () => {
      axios.post('/api/train/passive/' + this.props.skill.id + '/' + this.props.characterId)
        .then((result) => {
          this.setState({
            loading: false,
          }, () => {
            this.props.setSuccessMessage(result.data.message);
            this.props.close();
          });
        }).catch((error) => {
        this.setState({loading: false});
        const response = error.response;

        if (response.status === 401) {
          return location.reload()
        }

        if (response.status === 429) {
          return window.location.replace('/game');
        }

        if (response.data.hasOwnProperty('message')) {
          this.setState({
            showError: true,
            errorMessage: result.data.message,
          });
        }

        if (response.data.hasOwnProperty('error')) {
          this.setState({
            showError: true,
            errorMessage: result.data.error,
          });
        }
      });
    });
  }

  futureBonus() {
    const coreBonus    = this.props.skill.passive_skill.bonus_per_level;
    const currentLevel = this.props.skill.current_level;

    return (currentLevel + 1) * coreBonus;
  }

  render() {
    return (
      <Modal
        show={this.props.open}
        onHide={this.props.close}
        backdrop="static"
      >
        <Modal.Header closeButton>
          <Modal.Title>Training: {this.props.skill.passive_skill.name}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {this.props.skill.passive_skill.description}
          <hr />
          {
            this.state.showError ?
              <AlertError icon={"fas fa-exclamation"} title={'Oops!'}>
                <p>{this.state.errorMessage}</p>
              </AlertError>
            : null
          }
          <dl className="mt-2 mb-4">
            <dt>Hours till finished:</dt>
            <dd>{this.props.skill.hours_to_next}</dd>
            <dt>Bonus when finished:</dt>
            <dd>{(this.futureBonus() * 100).toFixed(0)}%</dd>
          </dl>
          <AlertInfo icon={'fas fa-question-circle'} title="Remember">
            <p>
              Remember that you cannot train other passives while this one is training. Even if you have
              them unlocked. You may only train one at a time.
            </p>
            <p>
              You may, of course, log out, as these can train in the background.
              When finished, you will see a notification in the notification center.
            </p>
            <p>
              <em>Notification center is found in the above top bar beside the "Help I'm Stuck" link.</em>
            </p>
            <p>
              There is no consequence for canceling a training skill, you can start another one or the same one
              again. The only consequence is it will still take the same amount of hours that it states.
            </p>
          </AlertInfo>

          {
            this.state.loading ?
              <div className="progress" style={{position: 'relative', height: '4px'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
              :
              null
          }
        </Modal.Body>
        <Modal.Footer>
          <Button variant="secondary" onClick={this.props.close}>
            Close
          </Button>
          <Button variant="success" onClick={this.trainSkill.bind(this)}>
            Train Skill
          </Button>
        </Modal.Footer>
      </Modal>
    )
  }
}
