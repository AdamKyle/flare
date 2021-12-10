import React, {Fragment} from 'react';
import moment from "moment";
import {CountdownCircleTimer} from "react-countdown-circle-timer";
import AlertInfo from "../../../../components/base/alert-info";

export default class SkillNode extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      timeRemaining: 0,
      forSkillId: null,
    }
  }

  componentDidMount() {
    this.setTimeRemaining();
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    if (this.state.timeRemaining === 0 && this.state.forSkillId === null) {
      this.setTimeRemaining();
    }

    if (this.state.timeRemaining !== 0 && this.state.forSkillId !== null) {
      let anySkillsTraining    = this.getPassiveInTraining(this.props.passive);

      if (anySkillsTraining === null) {
        this.setState({
          timeRemaining: 0,
          forSkillId: null,
        }, () => {
          this.props.updateTimer(false);
        });
      }
    }
  }

  setTimeRemaining() {
    let currentTrainingSkill = this.getPassiveInTraining(this.props.passive);

    if (currentTrainingSkill !== null) {
      this.setState({
        timeRemaining: currentTrainingSkill.completed_at,
        forSkillId: currentTrainingSkill.id,
      }, () => {
        this.props.updateTimer(true);
      });
    }
  }

  skillIsMaxed(passiveSkill) {
    return passiveSkill.current_level === passiveSkill.max_level;
  }

  getPassiveInTraining(passive) {
    if (passive.started_at !== null) {
      return passive
    }

    if (passive.children.length > 0) {
      for (const child of passive.children) {
        const foundPassive = this.getPassiveInTraining(child);

        if (typeof foundPassive !== null) {
          return foundPassive;
        }
      }
    }

    return null;
  }

  fetchTime(time, passiveSkillId) {
    if (passiveSkillId !== this.state.forSkillId) {
      time = 0;
    }

    let now = moment();
    let then = moment(time);

    let duration = moment.duration(then.diff(now)).asSeconds();

    const isHours = (duration / 3600) >= 1;

    if (duration > 0) {
      return (
        <Fragment>
          <div style={{marginLeft: '5px'}}>
            {isHours ?
              <CountdownCircleTimer
                isPlaying={true}
                duration={duration}
                initialRemainingTime={duration}
                colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
                size={40}
                strokeWidth={2}
                onComplete={() => [false, 0]}
              >
                {({remainingTime}) => (remainingTime / 3600).toFixed(0)}
              </CountdownCircleTimer>
              :
              <CountdownCircleTimer
                isPlaying={true}
                duration={duration}
                initialRemainingTime={duration}
                colors={[["#004777", 0.33], ["#F7B801", 0.33], ["#A30000"]]}
                size={40}
                strokeWidth={2}
                onComplete={() => [false, 0]}
              >
                {({remainingTime}) => (remainingTime / 60).toFixed(0)}
              </CountdownCircleTimer>
            }
          </div>
          <div>{isHours ? 'Hours' : 'Minutes'}</div>
        </Fragment>

      );
    } else {
      return null;
    }
  }

  render() {
    console.log(this.props.passive.name, this.props.timerIsRunning)
    return (
      <div>
        <div><strong>
          {
            this.props.passive.is_locked ?
              <a href={'/view/passive/'+this.props.passive.id+'/'+this.props.characterId} target="_blank" className="text-danger">
                {this.props.passive.name} <i className="fas fa-lock"></i>
              </a>
              :
              <a href={'/view/passive/'+this.props.passive.id+'/'+this.props.characterId} target="_blank">
                {this.props.passive.name} {this.skillIsMaxed(this.props.passive) ? <i className="fas fa-check text-success"></i> : null }
              </a>
          }
        </strong></div>
        <div>
          <strong>Current Level</strong>: {this.props.passive.current_level}</div>
        <div>
          <strong>Time Till Next</strong>: {this.skillIsMaxed(this.props.passive) ? 'Maxed' : this.props.passive.hours_to_next  + ' Hrs.'}
        </div>
        <div>
          {
            this.props.passive.started_at !== null ?
              <Fragment>
                <button className="btn btn-sm btn-primary"
                        onClick={() => this.props.managePassiveTrainingModal(this.props.passive)}
                        disabled={this.props.passive.is_locked || this.props.isTimerRunning || this.skillIsMaxed(this.props.passive)}
                >
                  Train
                </button>
                <button className="btn btn-sm btn-danger ml-2"
                        onClick={() => this.props.cancelPassiveTrain(this.props.passive)}
                >
                  Stop
                </button>
              </Fragment>
              :
              <button className="btn btn-sm btn-primary"
                      onClick={() => this.props.managePassiveTrainingModal(this.props.passive)}
                      disabled={this.props.passive.is_locked || this.props.isTimerRunning || this.skillIsMaxed(this.props.passive)}
              >
                Train
              </button>
          }
        </div>
        <div className="parent">
          <div className="child">
            {this.fetchTime(this.state.timeRemaining, this.props.passive.id)}
          </div>
        </div>
      </div>
    )
  }
}