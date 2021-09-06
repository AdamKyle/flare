import React from 'react';

export default class AffixData extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    const item = this.props.item['item_' + this.props.type];

    return (
      <>
        <h5 className="mb-2">{this.props.type.charAt(0).toUpperCase() + this.props.type.slice(1)}</h5>
        <p>{item.description}</p>
        <hr/>
        <dl>
          <dt>Name:</dt>
          <dd>{item.name}</dd>
          <dt>Base Damage Modifier:</dt>
          <dd>{item.base_damage_mod !== null ? item.base_damage_mod * 100 : 0}%</dd>
          <dt>Base AC Modifier:</dt>
          <dd>{item.base_ac_mod !== null ? item.base_ac_mod * 100 : 0}%</dd>
          <dt>Base Healing Modifier:</dt>
          <dd>{item.base_healing_mod !== null ? item.base_healing_mod * 100 : 0}%</dd>
          <dt>Base Fight Timeout Mod:</dt>
          <dd>{(item.fight_time_out_mod_bonus * 100).toFixed(0)}%</dd>
          <dt>Str Modifier:</dt>
          <dd>{item.str_mod !== null ? (item.str_mod * 100).toFixed(0) : 0}%</dd>
          <dt>Dex Modifier:</dt>
          <dd>{item.dex_mod !== null ? (item.dex_mod * 100).toFixed(0) : 0}%</dd>
          <dt>Dur Modifier:</dt>
          <dd>{item.dur_mod !== null ? (item.dur_mod * 100).toFixed(0) : 0}%</dd>
          <dt>Int Modifier:</dt>
          <dd>{item.int_mod !== null ? (item.int_mod * 100).toFixed(0) : 0}%</dd>
          <dt>Chr Modifier:</dt>
          <dd>{item.chr_mod !== null ? (item.chr_mod * 100).toFixed(0) : 0}%</dd>
          <dt>Skill Name:</dt>
          <dd>{item.skill_name === null ? 'N/A' : item.skill_name}</dd>
          <dt>Skill XP Bonus (When training):</dt>
          <dd>{item.skill_name === null ? 0 : item.skill_training_bonus * 100}%</dd>
          <dt>Skill Bonus (When Using):</dt>
          <dd>{item.skill_name === null ? 0 : item.skill_bonus * 100}%</dd>
        </dl>
        <hr/>
      </>
    )
  }
}
