import React, {Fragment} from 'react';
import {Card} from 'react-bootstrap';

export default class UsableItemDetails extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    return (
      <div className="mb-3">
        <Card>
          <Card.Body>
            <h3>Item Details</h3>
            <hr />
            {
              this.props.item.damages_kingdoms ?
                <>
                  <dl className="mt-2">
                    <dt>Damages Kingdom For:</dt>
                    <dd>{(this.props.item.kingdom_damage * 100).toFixed(0)}%</dd>
                  </dl>
                </>
              :
                this.props.item.stat_increase ?
                  <>
                    <dl className="mt-2">
                      <dd>Increases all core stats by:</dd>
                      <dt>{(this.props.item.increase_stat_by * 100).toFixed(0)}%</dt>
                    </dl>
                  </>
                :
                  <>
                    <dl className="mt-2">
                      {
                        this.props.item.affects_skills.length > 0 ?
                          <Fragment>
                            <dt>Skills Affected: </dt>
                            <dd>{this.props.item.affects_skills.join(', ')}</dd>
                            <dt>Skill Bonus: </dt>
                            <dd>{(this.props.item.increase_skill_bonus_by * 100)}%</dd>
                            <dt>Skill Training Bonus: </dt>
                            <dd>{(this.props.item.increase_skill_training_bonus_by * 100)}%</dd>
                            <dt>Skill Damage Mod Bonus:</dt>
                            <dd>{(this.props.item.base_damage_mod_bonus * 100)}%</dd>
                            <dt>Skill Healing Mod Bonus:</dt>
                            <dd>{(this.props.item.base_healing_mod_bonus * 100)}%</dd>
                            <dt>Skill AC Mod Bonus:</dt>
                            <dd>{(this.props.item.base_ac_mod_bonus * 100)}%</dd>
                            <dt>Skill Fight Timeout Mod Bonus:</dt>
                            <dd>{(this.props.item.fight_time_out_mod_bonus * 100)}%</dd>
                            <dt>Skill Move Timeout Mod Bonus:</dt>
                            <dd>{(this.props.item.move_time_out_mod_bonus)}%</dd>
                            <dt>Skill Bonus</dt>
                            <dd>{(this.props.item.skill_bonus * 100).toFixed(2)} %</dd>
                            <dt>Skill XP Bonus</dt>
                            <dd>{(this.props.item.skill_training_bonus * 100).toFixed(2)} %</dd>
                          </Fragment>
                        : null
                      }
                    </dl>
                    <dl className="mt-2">
                      {
                        this.props.item.base_damage_mod > 0.0 ?
                          <Fragment>
                            <dt>Base Damage Mod</dt>
                            <dd>{(this.props.item.base_damage_mod * 100).toFixed(2)} %</dd>
                          </Fragment>
                          : null
                      }
                      {
                        this.props.item.base_ac_mod !== null ?
                          <Fragment>
                            <dt>Base AC Mod</dt>
                            <dd>{(this.props.item.base_ac_mod * 100).toFixed(2)} %</dd>
                          </Fragment>
                          : null
                      }
                      {
                        this.props.item.base_healing_mod !== null ?
                          <Fragment>
                            <dt>Base Healing Mod</dt>
                            <dd>{(this.props.item.base_healing_mod * 100).toFixed(2)} %</dd>
                          </Fragment>
                          : null
                      }
                    </dl>
                    <dl className="mt-2">
                      {
                        this.props.item.stat_increase > 0.0 ?
                          <Fragment>
                            <dt>All Core Stat Modifier</dt>
                            <dd>{(this.props.boon.increase_stat_by * 100).toFixed(2)}%</dd>
                          </Fragment>
                          : null
                      }
                      {
                        this.props.item.str_modifier > 0.0 ?
                          <Fragment>
                            <dt>Str Modifier</dt>
                            <dd>{(this.props.item.str_modifier * 100).toFixed(2)}%</dd>
                          </Fragment>
                          : null
                      }
                      {
                        this.props.item.dex_modifier > 0.0 ?
                          <Fragment>
                            <dt>Dex Modifier</dt>
                            <dd>{(this.props.item.dex_modifier * 100).toFixed(2)}%</dd>
                          </Fragment>
                          : null
                      }
                      {
                        this.props.item.dur_modifier > 0.0 ?
                          <Fragment>
                            <dt>Dur Modifier</dt>
                            <dd>{(this.props.item.dur_modifier * 100).toFixed(2)}%</dd>
                          </Fragment>
                          : null
                      }
                      {
                        this.props.item.int_modifier > 0.0 ?
                          <Fragment>
                            <dt>Int Modifier</dt>
                            <dd>{(this.props.item.int_modifier * 100).toFixed(2)}%</dd>
                          </Fragment>
                          : null
                      }
                      {
                        this.props.item.chr_modifier > 0.0 ?
                          <Fragment>
                            <dt>Chr Modifier</dt>
                            <dd>{(this.props.item.chr_modifier * 100).toFixed(2)}%</dd>
                          </Fragment>
                          : null
                      }
                      {
                        this.props.item.agi_modifier > 0.0 ?
                          <Fragment>
                            <dt>AGI Modifier</dt>
                            <dd>{(this.props.item.agi_modifier * 100).toFixed(2)}%</dd>
                          </Fragment>
                          : null
                      }
                      {
                        this.props.item.focus_modifier > 0.0 ?
                          <Fragment>
                            <dt>Focus Modifier</dt>
                            <dd>{(this.props.item.focus_modifier * 100).toFixed(2)}%</dd>
                          </Fragment>
                          : null
                      }
                    </dl>
                  </>

            }
          </Card.Body>
        </Card>
      </div>
    )
  }
}
