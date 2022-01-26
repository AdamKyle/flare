import React, {Fragment} from 'react';
import {Tabs, Tab} from "react-bootstrap";

export default class AffixData extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    const item = this.props.item['item_' + this.props.type];

    const baseInfoId  = Math.random().toString(36).substr(2, 5);
    const statsId     = Math.random().toString(36).substr(2, 5);
    const skillsId    = Math.random().toString(36).substr(2, 5);
    const damageId    = Math.random().toString(36).substr(2, 5);
    const reductionId = Math.random().toString(36).substr(2, 5);

    return (
      <>
        <h5 className="mb-2">{this.props.type.charAt(0).toUpperCase() + this.props.type.slice(1)}</h5>
        <p>{item.description}</p>
        <hr/>
        <Tabs defaultActiveKey={'base-information-' + baseInfoId} id={item.id}>
          <Tab eventKey={'base-information-' + baseInfoId} title="Base Information">
            <dl className="mt-4">
              <dt>Name:</dt>
              <dd>{item.name}</dd>
              <dt>Base Attack Modifier:</dt>
              <dd>{item.base_damage_mod !== null ? item.base_damage_mod * 100 : 0}%</dd>
              <dt>Skill Damage Modifier:</dt>
              <dd>{item.base_damage_mod_bonus !== null ? item.base_damage_mod_bonus * 100 : 0}%</dd>
              <dt>Base AC Modifier:</dt>
              <dd>{item.base_ac_mod !== null ? item.base_ac_mod * 100 : 0}%</dd>
              <dt>Skill AC Modifier:</dt>
              <dd>{item.base_ac_mod_bonus !== null ? item.base_ac_mod_bonus * 100 : 0}%</dd>
              <dt>Base Healing Modifier:</dt>
              <dd>{item.base_healing_mod !== null ? item.base_healing_mod * 100 : 0}%</dd>
              <dt>Skill Healing Modifier:</dt>
              <dd>{item.base_healing_mod_bonus !== null ? item.base_healing_mod_bonus * 100 : 0}%</dd>
              <dt>Class Bonus:</dt>
              <dd>{item.class_bonus !== null ? item.class_bonus * 100 : 0}%</dd>
              <dt>Base Fight Timeout Mod:</dt>
              <dd>{(item.fight_time_out_mod_bonus * 100).toFixed(2)}%</dd>
              <dt>Base Move Timeout Mod:</dt>
              <dd>{(item.move_time_out_mod_bonus * 100).toFixed(2)}%</dd>
            </dl>
          </Tab>
          <Tab eventKey={'stats-' + statsId} title="Stats">
            <div className="row mt-4">
              <div className={item.reduces_enemy_stats ? 'col-md-6' : 'col-md-12'}>
                <dl>
                  <dt><i className="fas fa-level-up-alt text-success"></i> Str Mod:</dt>
                  <dd>{item.str_mod !== null ? (item.str_mod * 100).toFixed(2) : 0}%</dd>
                  <dt><i className="fas fa-level-up-alt text-success"></i> Dex Mod:</dt>
                  <dd>{item.dex_mod !== null ? (item.dex_mod * 100).toFixed(2) : 0}%</dd>
                  <dt><i className="fas fa-level-up-alt text-success"></i> Dur Mod:</dt>
                  <dd>{item.dur_mod !== null ? (item.dur_mod * 100).toFixed(2) : 0}%</dd>
                  <dt><i className="fas fa-level-up-alt text-success"></i> Int Mod:</dt>
                  <dd>{item.int_mod !== null ? (item.int_mod * 100).toFixed(2) : 0}%</dd>
                  <dt><i className="fas fa-level-up-alt text-success"></i> Chr Mod:</dt>
                  <dd>{item.chr_mod !== null ? (item.chr_mod * 100).toFixed(2) : 0}%</dd>
                  <dt><i className="fas fa-level-up-alt text-success"></i> Agi Mod:</dt>
                  <dd>{item.agi_mod !== null ? (item.agi_mod * 100).toFixed(2) : 0}%</dd>
                  <dt><i className="fas fa-level-up-alt text-success"></i> Focus Mod:</dt>
                  <dd>{item.focus_mod !== null ? (item.focus_mod * 100).toFixed(2) : 0}%</dd>
                </dl>
              </div>
              <div className={item.reduces_enemy_stats ? 'col-md-6' : 'hide'}>
                <dl>
                  <dt><i className="fas fa-level-down-alt text-danger"></i> Str Mod:</dt>
                  <dd>{item.str_mod !== null ? (item.str_reduction * 100).toFixed(2) : 0}%</dd>
                  <dt><i className="fas fa-level-down-alt text-danger"></i> Dex Mod:</dt>
                  <dd>{item.dex_mod !== null ? (item.dex_reduction * 100).toFixed(2) : 0}%</dd>
                  <dt><i className="fas fa-level-down-alt text-danger"></i> Dur Mod:</dt>
                  <dd>{item.dur_mod !== null ? (item.dur_reduction * 100).toFixed(2) : 0}%</dd>
                  <dt><i className="fas fa-level-down-alt text-danger"></i> Int Mod:</dt>
                  <dd>{item.int_mod !== null ? (item.int_reduction * 100).toFixed(2) : 0}%</dd>
                  <dt><i className="fas fa-level-down-alt text-danger"></i> Chr Mod:</dt>
                  <dd>{item.chr_mod !== null ? (item.chr_reduction * 100).toFixed(2) : 0}%</dd>
                  <dt><i className="fas fa-level-down-alt text-danger"></i> Agi Mod:</dt>
                  <dd>{item.agi_mod !== null ? (item.agi_reduction * 100).toFixed(2) : 0}%</dd>
                  <dt><i className="fas fa-level-down-alt text-danger"></i> Focus Mod:</dt>
                  <dd>{item.focus_mod !== null ? (item.focus_reduction * 100).toFixed(2) : 0}%</dd>
                </dl>
                <p className="mt-2 text-info">Affects enemies only.</p>
              </div>
            </div>
          </Tab>
          <Tab eventKey={'skills-' + skillsId} title="Skills">
            <div className="row">
              <div className={item.skill_reduction > 0 ? 'col-md-6' : 'col-md-12'}>
                <dl className="mt-4">
                  <dt>Skill Name:</dt>
                  <dd>{item.skill_name === null ? 'N/A' : item.skill_name}</dd>
                  <dt>Skill XP Bonus (When training):</dt>
                  <dd>{item.skill_name === null ? 0 : item.skill_training_bonus * 100}%</dd>
                  <dt>Skill Bonus (When Using):</dt>
                  <dd>{item.skill_name === null ? 0 : item.skill_bonus * 100}%</dd>
                </dl>
              </div>
              <div className={item.skill_reduction > 0 ? 'col-md-6' : 'hide'}>
                <dl className="mt-4">
                  <dt>Skills Affected:</dt>
                  <dd>Accuracy, Criticality, Casting Accuracy and Dodge</dd>
                  <dt>Skill Reduction %:</dt>
                  <dd
                    className={item.skill_reduction > 0.0 ? 'text-danger' : ''}>{item.skill_reduction * 100}%
                  </dd>
                </dl>

                <p className="mt-2 text-info">Affects enemies only.</p>
              </div>
            </div>

          </Tab>
          <Tab eventKey={'damage-' + damageId} title="Damage">
            <dl className="mt-4">
              <dt>Damage:</dt>
              <dd>{item.damage}</dd>
              <dt>Is Damage Irresistible?:</dt>
              <dd>{item.irresistible_damage ? 'Yes' : 'No'}</dd>
              <dt>Can Stack:</dt>
              <dd>{item.damage_can_stack ? 'Yes' : 'No'}</dd>
              {
                item.steal_life_amount !== null ?
                  <Fragment>
                    <dt>Steal Life Amount:</dt>
                    <dd>{item.steal_life_amount * 100}%</dd>
                  </Fragment>
                :
                  null
              }

              {
                item.entranced_chance > 0.0 ?
                  <Fragment>
                    <dt>Entrance Chance:</dt>
                    <dd>{item.entranced_chance * 100}%</dd>
                  </Fragment>
                :
                  null
              }

              {
                item.devouring_light > 0.0 ?
                  <Fragment>
                    <dt>Devouring Light Chance:</dt>
                    <dd>{item.devouring_light * 100}%</dd>
                  </Fragment>
                  :
                  null
              }
            </dl>
          </Tab>
          <Tab eventKey={'resistance-reduction' + reductionId} title="Resistance Reduction">
            <dl className="mt-4">
              <dt>Resistance Reduction:</dt>
              <dd className={item.resistance_reduction > 0 ? 'text-danger' : ''}>{item.resistance_reduction * 100}%</dd>
            </dl>
          </Tab>
        </Tabs>
      </>
    )
  }
}
