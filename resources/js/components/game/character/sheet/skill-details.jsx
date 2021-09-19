import React, {Fragment} from 'react';
import {Card, Tab, Tabs, OverlayTrigger, Tooltip} from "react-bootstrap";

const renderTooltip = (xpTowards) => (
  <Tooltip id="button-tooltip">
    Xp Towards: {xpTowards}%
  </Tooltip>
);

export default class SkillDetails extends React.Component {

  constructor(props) {
    super(props);

    this.state = {
      showTrainModal: false,
      successMessage: null,
    }
  }

  clearSuccessMessage() {
    this.setState({
      successMessage: null,
    })
  }

  setSuccessMessage(message) {
    this.setState({
      successMessage: message,
    })
  }

  manageTrainSkill() {
    this.setState({
      showTrainModal: !this.state.showTrainModal
    })
  }


  renderSkills(type) {
    return this.props.skills.map((s) => s.skill_type === type || s.skill_type === 'Effects Directional Move Timer' || s.skill_typw === 'Effects Battle Timer' ?
      <Fragment>
        <dt>
          <a href={"/skill/" + s.id}
             target="_blank"
             className={s.is_locked ? 'text-danger' : ''}
          >
            {s.name} {s.is_locked ? <i className="fas fa-lock"></i> : null}
          </a>
        </dt>
        <dd>
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
                    disabled={!this.props.canAdventure || this.props.isDead}
                  >
                    Train { s.is_training ? <i className="ml-2 fas fa-check"></i> : null }
                  </button>
                  {
                    s.is_training ?
                      <Fragment>
                        <button
                          className="btn btn-danger btn-sm ml-2 train-skill-btn"
                          disabled={!this.props.canAdventure || this.props.isDead}
                        >
                          Stop
                        </button>
                        <OverlayTrigger
                          placement="right"
                          delay={{ show: 250, hide: 400 }}
                          overlay={renderTooltip(s.xp_towards)}
                        >
                          <i class="ml-2 fas fa-info-circle skill-info-icon text-info"></i>
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
      <Fragment>
        <dt>
          <a href={"/skill/" + s.id}
             target="_blank"
             className={s.is_locked ? 'text-danger' : ''}
          >
            {s.name} {s.is_locked ? <i className="fas fa-lock"></i> : null}
          </a>
        </dt>
        <dd>
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
      && s.skill_type !== 'Effects Battle Timer'?
      <Fragment>
        <dt>
          <a href={"/skill/" + s.id}
             target="_blank"
             className={s.is_locked ? 'text-danger' : ''}
          >
            {s.name} {s.is_locked ? <i className="fas fa-lock"></i> : null}
          </a>
        </dt>
        <dd>
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

  render() {
    return (
      <Card>
        <Card.Body>
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
                <dl className="mt-4">{this.renderSkills('Training')}</dl>
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
          </Tabs>
        </Card.Body>
      </Card>
    );
  }
}