import React from 'react';

export default class CharacterSheet extends React.Component {

  constructor(props) {
    super(props);
  }

  characterSkills(skills) {
    return skills.map((skill) => {
      const currentTraining = (skill.xp / skill.xp_max) * 100;
      const training        = skill.currently_training ? 'Yes' : 'No';

      return (
        <div key={skill.id}>
          <dl>
            <dt>{skill.name}:</dt>
            <dd>
              <div className="progress skill-training mb-2">
                <div className="progress-bar skill-bar" role="progressbar"
                  style={{width: currentTraining + '%'}}
                  aria-valuenow={skill.xp} aria-valuemin="0"
                  aria-valuemax={skill.xp_max}></div>
                  {'Level: ' + skill.level + ', Active: ' + training}
              </div>
            </dd>
          </dl>
        </div>
      )
    })
  }

  render() {
    const characterSheet = this.props.sheet;

    const xp = (characterSheet.xp / characterSheet.xp_next) * 100;

    return (
      <div>
        <div className="row">
          <div className="col-md-4">
            <dl>
              <dt>Name:</dt>
              <dd>{characterSheet.name}</dd>
            </dl>
            <dl>
              <dt>Race:</dt>
              <dd>{characterSheet.race}</dd>
            </dl>
            <dl>
              <dt>Class:</dt>
              <dd>{characterSheet.class}</dd>
            </dl>
            <dl>
              <dt>Level:</dt>
              <dd>{characterSheet.level}</dd>
            </dl>
          </div>
          <div className="col-md-4">
            <dl>
              <dt>Strength:</dt>
              <dd>{characterSheet.str}</dd>
            </dl>
            <dl>
              <dt>Dexterity:</dt>
              <dd>{characterSheet.dex}</dd>
            </dl>
            <dl>
              <dt>Durabillity:</dt>
              <dd>{characterSheet.dur}</dd>
            </dl>
            <dl>
              <dt>Charisma:</dt>
              <dd>{characterSheet.chr}</dd>
            </dl>
            <dl>
              <dt>Intelligence:</dt>
              <dd>{characterSheet.int}</dd>
            </dl>
          </div>
          <div className="col-md-4">
            {this.characterSkills(characterSheet.skills)}
          </div>
        </div>
        <hr />
        <div className="row mb-2">
          <div className="col-md-6">
            <dl>
              <dt>Max Health:</dt>
              <dd>{characterSheet.health}</dd>
            </dl>
            <dl>
              <dt>Armour Class:</dt>
              <dd>{characterSheet.ac}</dd>
            </dl>
            <dl>
              <dt>Damage Stat:</dt>
              <dd>{characterSheet.damage_stat}</dd>
            </dl>
            <dl>
              <dt>Max Attack:</dt>
              <dd>{characterSheet.attack}</dd>
            </dl>
          </div>
          <div className="col-md-6">
            <dl>
              <dt>Inventory Max:</dt>
              <dd>{characterSheet.inventory_max}</dd>
            </dl>
            <dl>
              <dt>Gold:</dt>
              <dd>{characterSheet.gold}</dd>
            </dl>
            <dl>
              <dt>XP:</dt>
              <dd>
                <div className="progress skill-training mb-2">
                  <div className="progress-bar skill-bar" role="progressbar"
                    style={{width: xp + '%'}}
                    aria-valuenow={characterSheet.xp} aria-valuemin="0"
                    aria-valuemax={CharacterSheet.xp_next}>{characterSheet.xp}</div>
                </div>
              </dd>
            </dl>
          </div>
        </div>
      </div>
    );
  }
}
