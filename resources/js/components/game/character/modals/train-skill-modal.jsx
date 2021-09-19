import React from 'react';
import {Modal, Button} from 'react-bootstrap';

export default class TrainSkillModal extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      loading: false,
      showError: false,
      errorMessage: null,
      selectedAmount: 0.0,
      availableOptions: [
        0.10, 0.20, 0.30, 0.40, 0.50,
        0.60, 0.70, 0.80, 0.90, 1
      ],
    }
  }

  componentDidMount() {
    this.setState({
      selectedAmount: this.props.skill.xp_towards,
    });
  }

  trainSkill() {
    this.setState({
      showError: false,
      errorMessage: null,
      loading: true,
    }, () => {
      if (this.state.selectedAmount === 0.0) {
        return this.setState({
          showError: true,
          errorMessage: 'Cannot train skill with no xp applied.',
          loading: false,
        });
      }

      axios.post('/api/skill/train/'+this.props.characterId, {
        skill_id: this.props.skill.id,
        xp_percentage: this.state.selectedAmount,
      })
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

  selectOptions() {
    return this.state.availableOptions.map((o) => <option value={o} key={o}>{o * 100}%</option>)
  }

  xpSelected(event) {
    this.setState({
      selectedAmount: parseFloat(event.target.value)
    });
  }

  render() {
    return (
      <Modal
        show={this.props.open}
        onHide={this.props.close}
        backdrop="static"
      >
        <Modal.Header closeButton>
          <Modal.Title>Training: {this.props.skill.name}</Modal.Title>
        </Modal.Header>
        <Modal.Body>
          {
            this.state.showError ?
              <div className="alert alert-danger mt-2 mb-3">
                <p>{this.state.errorMessage}</p>
              </div>
              : null
          }
          <p> To Train this skill please select how much XP you are willing to sacrifice per kill. That sacrificed XP then goes towards your
            skill xp and levels it over time. This is why leveling in areas with high XP rewards (such as Dungeons) and doing adventures
            that reward both high XP and high skill xp will help to level skills faster.
          </p>
          <p>
            The more experience you sacrifice per kill, the less you get to level your character and thus you cannot move down the list.
            It is suggested players sacrifice 10% only when they are starting out and as they get closer to level cap, increase it.
          </p>
          <p>
            <select className="form-control monster-select" id="skill-xp-sacrifice" name="skill-xp-sacrifice"
                    value={this.state.selectedAmount}
                    onChange={this.xpSelected.bind(this)}
            >
              <option value={0.0} key="0">Please select an amount</option>
              {this.selectOptions()}
            </select>
          </p>
          {
            this.state.loading ?
              <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
              : null
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
