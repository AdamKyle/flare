import React from 'react';

export default class EquipDetails extends React.Component {

  constructor(props) {
    super(props)
  }

  render() {
    return (
      <>
        <div className="alert alert-info mb-3">
          Values include (any) attached affixes.
        </div>
        <div className="row">
          <div className="col-md-6">
          <dl>
            <dt><strong>Attack <sup>*</sup></strong>:</dt>
            <dd>{this.props.item.base_damage}</dd>
            <dt><strong>AC</strong>:</dt>
            <dd>{this.props.item.base_ac}</dd>
            <dt><strong>Healing</strong>:</dt>
            <dd>{this.props.item.base_healing}</dd>
            <dt><strong>Base Attack Mod</strong>:</dt>
            <dd>{this.props.item.base_damage_mod * 100}%</dd>
            <dt><strong>Fight Timeout Modifier <sup>**</sup></strong>:</dt>
            <dd>{this.props.item.fight_time_out_mod_bonus * 100}%</dd>
            <dt><strong>Base Damage Modifier <sup>**</sup></strong>:</dt>
            <dd>{this.props.item.base_damage_mod_bonus * 100}%</dd>
            <dt><strong>AC Mod</strong>:</dt>
            <dd>{this.props.item.base_ac_mod * 100}%</dd>
            <dt><strong>Spell Evasion Modifier</strong>:</dt>
            <dd>{this.props.item.spell_evasion * 100}%</dd>
            <dt><strong>Artifact Annulment Modifier</strong>:</dt>
            <dd>{this.props.item.artifact_annulment * 100}%</dd>
            {
              this.props.item.can_resurrect ?
                <>
                  <dt>Resurrection Chance <sup>rc</sup>:</dt>
                  <dd>{this.props.item.resurrection_chance * 100}%</dd>
                </>
                : null
            }

          </dl>
          </div>
          <div className="col-md-6">
            <dl>
              <dt><strong>Strength</strong>:</dt>
              <dd>{(this.props.item.str_modifier * 100).toFixed(0)}%</dd>
              <dt><strong>Durability</strong>:</dt>
              <dd>{(this.props.item.dur_modifier * 100).toFixed(0)}%</dd>
              <dt><strong>Dexterity</strong>:</dt>
              <dd>{(this.props.item.dex_modifier * 100).toFixed(0)}%</dd>
              <dt><strong>Charisma</strong>:</dt>
              <dd>{(this.props.item.chr_modifier * 100).toFixed(0)}%</dd>
              <dt><strong>Intelligence</strong>:</dt>
              <dd>{(this.props.item.int_modifier * 100).toFixed(0)}%</dd>
              <dt><strong>Agility</strong>:</dt>
              <dd>{(this.props.item.agi_modifier * 100).toFixed(0)}%</dd>
              <dt><strong>Focus</strong>:</dt>
              <dd>{(this.props.item.focus_modifier * 100).toFixed(0)}%</dd>
            </dl>
          </div>
        </div>
        <p className="mt-3 mb-3">
          <sup>*</sup> Attack includes Base Attack Modifier applied automatically, rounded to the nearest
          whole number.
        </p>
        <p>
          <sup>**</sup> Applies to all skills that increase this modifier.
        </p>
        {
          this.props.item.can_resurrect ?
            <p>
              <sup>rc</sup> Used to determine, upon death in either battle or adventure, if your character can automatically resurrect and heal.
            </p>
            : null
        }
      </>
    )
  }
}
