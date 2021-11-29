import React, {Fragment} from 'react';
import {Card, Tab, Tabs, OverlayTrigger, Tooltip, Alert} from "react-bootstrap";
import TrainSkillModal from "../modals/train-skill-modal";
import TrainPassiveSkillModal from "../modals/train-passive-skill-modal";
import moment from "moment";
import {CountdownCircleTimer} from "react-countdown-circle-timer";
import AlertInfo from "../../components/base/alert-info";
import AlertWarning from "../../components/base/alert-warning";

const renderTooltip = (xpTowards) => (
  <Tooltip id="button-tooltip">
    Xp Towards: {xpTowards * 100}%
  </Tooltip>
);

export default class SkillDetails extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      showTrainModal: false,
      successMessage: null,
      errorMessage: null,
      skillToTrain: null,
      loading: false,
      showTrainPassiveModal: false,
      passiveSkillToTrain: null,
      timeRemaining: 0,
      forPassiveSkill: null,
    }
  }

  componentDidMount() {
    this.setTimeRemaining();
  }

  componentDidUpdate(prevProps, prevState, snapshot) {
    if (this.state.timeRemaining === 0 && this.state.forPassiveSkill === null) {
      this.setTimeRemaining();
    }

    if (this.state.timeRemaining !== 0 && this.state.forPassiveSkill !== null) {
      let anySkillsTraining    = this.props.passiveSkills.filter((ps) => ps.started_at === null && ps.id === this.state.forPassiveSkill);

      if (anySkillsTraining.length > 0) {
        this.setState({
          timeRemaining: 0,
          forPassiveSkill: null,
        });
      }
    }
  }

  setTimeRemaining() {
    let currentTrainingSkill = this.props.passiveSkills.filter((ps) => ps.started_at !== null);

    if (currentTrainingSkill.length > 0) {
      currentTrainingSkill = currentTrainingSkill[0];

      this.setState({
        timeRemaining: currentTrainingSkill.completed_at,
        forPassiveSkill: currentTrainingSkill.id,
      });
    }
  }

  clearSuccessMessage() {
    this.setState({
      successMessage: null,
    });
  }

  setSuccessMessage(message) {
    this.setState({
      successMessage: message,
    });
  }

  clearErrorMessage() {
    this.setState({
      errorMessage: null,
    });
  }

  manageTrainSkill(skill) {
    this.setState({
      showTrainModal: !this.state.showTrainModal,
      skillToTrain: typeof skill !== 'undefined' ? skill : null,
    });
  }

  managePassiveTrainingModal(skill) {
    this.setState({
      showTrainPassiveModal: !this.state.showTrainPassiveModal,
      passiveSkillToTrain: typeof skill !== 'undefined' ? skill : null,
    });
  }

  hasAnySkillInTraining() {
    return this.props.passiveSkills.filter((ps) => ps.started_at !== null).length > 0
  }

  skillIsMaxed(passiveSkill) {
    return passiveSkill.current_level === passiveSkill.passive_skill.max_level;
  }

  stopTrainingSkill(skill) {
    this.setState({
      showError: false,
      errorMessage: null,
      successMessage: null,
      loading: true,
    }, () => {
      axios.post('/api/skill/cancel-train/' + this.props.characterId + '/' + skill.id)
        .then((result) => {
          this.setState({
            loading: false,
          }, () => {
            this.setSuccessMessage(result.data.message);
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

  cancelPassiveTrain(passiveSkill) {
    this.setState({
      showError: false,
      errorMessage: null,
      successMessage: null,
      loading: true,
    }, () => {
      axios.post('/api/stop-training/passive/' + passiveSkill.id + '/' + this.props.characterId)
        .then((result) => {
          this.setState({
            loading: false,
          }, () => {
            this.setSuccessMessage(result.data.message);
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


  renderSkills() {
    return this.props.skills.map((s) => s.can_train ?
      <Fragment key={Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5)}>
        <dt key={Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5)}>
          <a href={"/skill/" + s.id}
             target="_blank"
             className={s.is_locked ? 'text-danger' : ''}
          >
            {s.name} {s.is_locked ? <i className="fas fa-lock"></i> : null}
          </a>
        </dt>
        <dd key={Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5)}>
          <div className="row">
            <div className={s.can_train ? 'col-xs-12 col-sm-3' : 'col-xs-12 col-sm-4'}>
              Level: {s.level} / {s.max_level}
            </div>
            <div className={s.can_train ? 'col-xs-12 col-sm-3' : 'col-xs-12 col-sm-4'}>
              XP: {s.xp} / {s.xp_max}
            </div>
            <div className={s.can_train ? 'col-xs-12 col-sm-2' : 'col-xs-12 col-sm-4'}>
              <div className="progress level-bar mb-2">
                <div className="progress-bar skill-bar" role="progressbar"
                     style={{width: s.current_xp + '%'}}
                     aria-valuenow={s.xp} aria-valuemin="0"
                     aria-valuemax={s.xp_max}
                >
                  {Math.round(s.xp)}
                </div>
              </div>
            </div>
            {
              s.can_train ?
                <div className="col-xs-12 col-sm-4">
                  <button
                    className={s.is_training ? 'btn btn-success btn-sm train-skill-btn' : 'btn btn-primary btn-sm train-skill-btn'}
                    disabled={!this.props.canAdventure || this.props.isDead || this.props.automations.length > 0}
                    onClick={() => this.manageTrainSkill(s)}
                  >
                    Train { s.is_training ? <i className="ml-2 fas fa-check"></i> : null }
                  </button>
                  {
                    s.is_training ?
                      <Fragment>
                        <button
                          className="btn btn-danger btn-sm ml-2 train-skill-btn"
                          disabled={!this.props.canAdventure || this.props.isDead || this.props.automations.length > 0}
                          onClick={() => this.stopTrainingSkill(s)}
                        >
                          Stop
                        </button>
                        <OverlayTrigger
                          placement="right"
                          delay={{ show: 250, hide: 400 }}
                          overlay={renderTooltip(s.xp_towards)}
                        >
                          <i className="ml-2 fas fa-info-circle skill-info-icon text-info"></i>
                        </OverlayTrigger>
                      </Fragment>
                    : null
                  }
                </div>
              : null
            }
          </div>
        </dd>
      </Fragment>
      : null
    )
  }

  renderCraftingSkills() {
    return this.props.skills.map((s) => s.skill_type === 'Crafting' || s.skill_type === 'Enchanting' || s.skill_type == 'Alchemy'   ?
      <Fragment key={Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5)}>
        <dt key={Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5)}>
          <a href={"/skill/" + s.id}
             target="_blank"
             className={s.is_locked ? 'text-danger' : ''}
          >
            {s.name} {s.is_locked ? <i className="fas fa-lock"></i> : null}
          </a>
        </dt>
        <dd key={Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5)}>
          <div className="row">
            <div className="col-xs-12 col-sm-4">
              Level: {s.level} / {s.max_level}
            </div>
            <div className="col-xs-12 col-sm-4">
              XP: {s.xp} / {s.xp_max}
            </div>
            <div className="col-xs-12 col-sm-4">
              <div className="progress level-bar mb-2">
                <div className="progress-bar skill-bar" role="progressbar"
                     style={{width: s.current_xp + '%'}}
                     aria-valuenow={s.xp} aria-valuemin="0"
                     aria-valuemax={s.xp_max}
                >
                  {Math.round(s.xp)}
                </div>
              </div>
            </div>
          </div>
        </dd>
      </Fragment>
      : null
    )
  }

  renderMiscSkills() {
    return this.props.skills.map((s) => s.skill_type !== 'Training'
      && s.skill_type !== 'Crafting'
      && s.skill_type !== 'Enchanting'
      && s.skill_type !== 'Alchemy'
      && s.skill_type !== 'Effects Directional Move Timer'
      && s.skill_type !== 'Effects Battle Timer'
      && !s.can_train ?
      <Fragment key={Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5)}>
        <dt key={Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5)}>
          <a href={"/skill/" + s.id}
             target="_blank"
             className={s.is_locked ? 'text-danger' : ''}
          >
            {s.name} {s.is_locked ? <i className="fas fa-lock"></i> : null}
          </a>
        </dt>
        <dd key={Math.random().toString(36).replace(/[^a-z]+/g, '').substr(0, 5)}>
          <div className="row">
            <div className="col-xs-12 col-sm-4">
              Level: {s.level} / {s.max_level}
            </div>
            <div className="col-xs-12 col-sm-4">
              XP: {s.xp} / {s.xp_max}
            </div>
            <div className="col-xs-12 col-sm-4">
              <div className="progress level-bar mb-2">
                <div className="progress-bar skill-bar" role="progressbar"
                     style={{width: s.current_xp + '%'}}
                     aria-valuenow={s.xp} aria-valuemin="0"
                     aria-valuemax={s.xp_max}
                >
                  {Math.round(s.xp)}
                </div>
              </div>
            </div>
          </div>
        </dd>
      </Fragment>
      : null
    )
  }

  renderPassiveSkills() {
    return this.props.passiveSkills.map((passiveSkill) =>
      <Fragment>
        {
          passiveSkill.started_at !== null ?
            <AlertInfo icon={"fas fa-question-circle"} title={"Attn!"}>
              <p>
                Canceling this skill, will still take the amount of hours as stated. The time you have spent training this skill,
                will <strong>not</strong> be taken into account when you go to train again.
              </p>
            </AlertInfo>
          : null
        }
        <dt>
          {
            passiveSkill.is_locked ?
              <a href={'/view/passive/'+passiveSkill.id+'/'+this.props.characterId} target="_blank" className="text-danger">
                {passiveSkill.passive_skill.name} <i className="fas fa-lock"></i>
              </a>
            :
              <a href={'/view/passive/'+passiveSkill.id+'/'+this.props.characterId} target="_blank">
                {passiveSkill.passive_skill.name}
              </a>
          }
        </dt>
        <dd>
          <div className="tw-pl-2 row">
            <div className="col-xs-12 col-sm-4">
              <strong>Current Level</strong>: {passiveSkill.current_level}
            </div>
            <div className="col-xs-12 col-sm-3">
              <strong>Time Till Next</strong>: {this.skillIsMaxed(passiveSkill) ? 'Maxed' : passiveSkill.hours_to_next  + ' Hr.'}
            </div>
            <div className="col-xs-12 col-sm-3">
              {
                this.skillIsMaxed(passiveSkill) ?
                  <i className="fas fa-check text-success"></i>
                :
                  passiveSkill.started_at !== null ?
                    <Fragment>
                      <button className="btn btn-sm btn-primary"
                              onClick={() => this.managePassiveTrainingModal(passiveSkill)}
                              disabled={passiveSkill.is_locked || this.hasAnySkillInTraining() || this.skillIsMaxed(passiveSkill)}
                      >
                        Train
                      </button>
                      <button className="btn btn-sm btn-danger ml-2"
                              onClick={() => this.cancelPassiveTrain(passiveSkill)}
                      >
                        Stop
                      </button>
                    </Fragment>
                  :
                    <button className="btn btn-sm btn-primary"
                            onClick={() => this.managePassiveTrainingModal(passiveSkill)}
                            disabled={passiveSkill.is_locked || this.hasAnySkillInTraining() || this.skillIsMaxed(passiveSkill)}
                    >
                      Train
                    </button>


              }
            </div>
            <div className="col-xs-12 col-sm-2">
              {this.fetchTime(this.state.timeRemaining, passiveSkill.id, this.state.forPassiveSkill)}
            </div>
          </div>
        </dd>
      </Fragment>
    );
  }

  fetchTime(time, passiveSkillId, forPassiveSkillId) {
    if (passiveSkillId !== forPassiveSkillId) {
      time = 0;
    }

    let now = moment();
    let then = moment(time);

    let duration = moment.duration(then.diff(now)).asSeconds();

    const isHours = (duration / 3600) >= 1;

    if (duration > 0) {
      return (
        <Fragment>
          <div style={{marginTop: '-10px'}}>
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
    return (
      <Card>
        <Card.Body>
          {
            this.state.successMessage !== null ?
              <div className="mt-2 mb-3">
                <Alert variant="success" onClose={this.clearSuccessMessage.bind(this)} dismissible>
                  {this.state.successMessage}
                </Alert>
              </div>
              : null
          }

          {
            this.state.errorMessage !== null ?
              <div className="mt-2 mb-3">
                <Alert variant="danger" onClose={this.clearErrorMessage.bind(this)} dismissible>
                  {this.state.errorMessage}
                </Alert>
              </div>
              : null
          }
          {
            this.props.automations.length > 0 ?
              <div className="mt-2 mb-3">
                <AlertWarning icon={'fas fa-exclamation-triangle'} title={'Automation is running'}>
                  <p>
                    You cannot modify any of your skills except your <strong>passive</strong> skills because you are currently
                    in the middle of an automation.
                  </p>
                  <p>
                    You can still disenchant, craft and enchant, however you cannot switch which skill is currently training in relation to
                    combat based skills.
                  </p>
                </AlertWarning>
              </div>
            : null
          }
          {
            this.state.loading ?
              <div className="progress loading-progress mt-2 mb-2" style={{position: 'relative'}}>
                <div className="progress-bar progress-bar-striped indeterminate">
                </div>
              </div>
              : null
          }
          <Tabs defaultActiveKey="training-skills" id="skill-section">
            <Tab eventKey="training-skills" title="Training Skills">
              <div className="character-skill-info">
                <p className="mt-4">
                  These are all your training skills. To train a skill simply click train beside the skill you want to
                  train. Then you will select a specific amount of XP to sacrifice from killing monsters, celestials and adventures
                  that will go towards leveling this skill.
                </p>
                <p>
                  You cannot train more then one skill at a time.
                </p>
                <hr />
                <dl className="mt-4">{this.renderSkills()}</dl>
              </div>
            </Tab>
            <Tab eventKey="crafting-skills" title="Crafting Skills">
              <div className="character-skill-info">
                <p className="mt-4">
                  These are your crafting skills. You train these by Selecting Craft from the Craft/Enchant or Enchant from the list.
                </p>
                <p>
                  Each type of item you can buy from the shop has a corresponding crafting skill. You gain XP by crafting and
                  enchanting items.
                </p>
                <p>
                  Alchemy is also a type of crafting skill that may need to be unlocked in order to use it from the same
                  drop down.
                </p>
                <hr />
                <dl className="mt-4">{this.renderCraftingSkills()}</dl>
              </div>
            </Tab>
            <Tab eventKey="misc-skills" title="Misc. Skills">
              <div className="character-skill-info">
                <p className="mt-4">
                  These are misc skills that are leveled by other actions in game. For example,
                  Disenchanting is leveled only by you physically disenchanting items.
                </p>
                <p>
                  The higher the level, the higher the skill bonus and with skills like disenchanting that means
                  you get a percentage of extra gold dust upon disenchanting successfully.
                </p>
                <hr />
                <dl className="mt-4">{this.renderMiscSkills()}</dl>
              </div>
            </Tab>
            <Tab eventKey="passive-skills" title="Passive Skills">
              <div className="character-skill-info">
                <p className="mt-4">
                  These are passive skills. These are trained over time. As you gain more levels, some of the passives below
                  will unlock and allow you to level them as well.
                </p>
                <p>
                  You can click on each passive skill and see what you get per level, what it unlocks as you level it
                  and how much time it will take to level the skill.
                </p>
                <hr />
                <dl className="mt-4">{this.renderPassiveSkills()}</dl>
              </div>
            </Tab>
          </Tabs>

          {
            this.state.showTrainModal && this.state.skillToTrain !== null ?
              <TrainSkillModal
                characterId={this.props.characterId}
                setSuccessMessage={this.setSuccessMessage.bind(this)}
                open={this.state.showTrainModal}
                close={this.manageTrainSkill.bind(this)}
                skill={this.state.skillToTrain}
              />
            : null
          }

          {
            this.state.showTrainPassiveModal && this.state.passiveSkillToTrain !== null ?
              <TrainPassiveSkillModal
                characterId={this.props.characterId}
                setSuccessMessage={this.setSuccessMessage.bind(this)}
                open={this.state.showTrainPassiveModal}
                close={this.managePassiveTrainingModal.bind(this)}
                skill={this.state.passiveSkillToTrain}
              />
              : null
          }
        </Card.Body>
      </Card>
    );
  }
}