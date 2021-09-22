import React from 'react';
import {Card, Col, Row} from 'react-bootstrap';

export default class CharacterDetails extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {

    const sheet = this.props.characterSheet;

    const xpValue = sheet.xp / sheet.xp_next * 100;

    return (
      <Card>
        <Card.Body>
          <Row>
            <Col xs={12} sm={6}>
              <dl>
                <dt>Character Name:</dt>
                <dd>{sheet.name}</dd>
                <dt>Character Race:</dt>
                <dd>{sheet.race}</dd>
                <dt>Character Class:</dt>
                <dd>{sheet.class}</dd>
                <dt>Character Level:</dt>
                <dd>{sheet.level} / {sheet.max_level}</dd>
                <dt>Character XP:</dt>
                <dd>
                  <div className="progress level-bar mb-2">
                    <div className="progress-bar skill-bar" role="progressbar"
                         style={{width: xpValue + '%'}}
                         aria-valuenow={sheet.xp} aria-valuemin="0"
                         aria-valuemax={sheet.xp_next}
                    >
                      {Math.round(sheet.xp)}
                    </div>
                  </div>
                </dd>
              </dl>
            </Col>
            <Col xs={12} sm={6}>
              <dl>
                <dt>Max Health:</dt>

                <dd>{sheet.health}</dd>
                <dt>Attack:</dt>
                <dd>{sheet.attack}</dd>
                <dt>Heal For:</dt>
                <dd>{sheet.heal_for}</dd>
                <dt>AC:</dt>
                <dd>{sheet.ac}</dd>
              </dl>
            </Col>
          </Row>
          <hr />
          <Row>
            <Col xs={12} sm={4}>
              <dl>
                <dt>Strength:</dt>
                <dd>{sheet.str}</dd>
                <dt>Durability:</dt>
                <dd>{sheet.dur}</dd>
                <dt>Dexterity:</dt>
                <dd>{sheet.dex}</dd>
                <dt>Charisma:</dt>
                <dd>{sheet.chr}</dd>
                <dt>Intelligence:</dt>
                <dd>{sheet.int}</dd>
                <dt>Agility:</dt>
                <dd>{sheet.agi}</dd>
                <dt>Focus:</dt>
                <dd>{sheet.focus}</dd>
              </dl>
            </Col>
            <Col xs={12} sm={4}>
              <dl>
                <dt>Strength Modded:</dt>
                <dd>{sheet.str_modded}</dd>
                <dt>Durability Modded:</dt>
                <dd>{sheet.dur_modded}</dd>
                <dt>Dexterity Modded:</dt>
                <dd>{sheet.dex_modded}</dd>
                <dt>Charisma Modded:</dt>
                <dd>{sheet.chr_modded}</dd>
                <dt>Intelligence Modded:</dt>
                <dd>{sheet.int_modded}</dd>
                <dt>Agility Modded:</dt>
                <dd>{sheet.agi_modded}</dd>
                <dt>Focus Modded:</dt>
                <dd>{sheet.docus_modded}</dd>
              </dl>
            </Col>
            <Col xs={12} sm={4}>
              <dl>
                <dt>Spell Evasion:</dt>
                <dd>{sheet.spell_evasion * 100}%</dd>
                <dt>Artifact Annulment:</dt>
                <dd>{sheet.artifact_anull * 100}%</dd>
                <dt>Resurrection Chance<sup>*</sup>:</dt>
                <dd>{sheet.res_chance * 100}%</dd>
              </dl>
              <p className="mt-4"><sup>*</sup> Only healing spells can affect this.</p>
            </Col>
          </Row>
          <hr />
          <Row>
            <Col xs={12} sm={6}>
              <h5>Attack Break Down</h5>
              <p className="mt-2">
                These include any attached affixes and skill bonuses:
              </p>
              <hr/>
              <dl>
                <dt>Weapon Attack:</dt>
                <dd>{sheet.weapon_attack}</dd>
                <dt>Rings Attack:</dt>
                <dd>{sheet.rings_attack}</dd>
                <dt>Spell Damage:</dt>
                <dd>{sheet.spell_damage}</dd>
                <dt>Artifact Damage:</dt>
                <dd>{sheet.artifact_damage}</dd>
                <dt>Heal For:</dt>
                <dd>{sheet.heal_for}</dd>
              </dl>
            </Col>
            <Col xs={12} sm={6}>
              <h5>Class Attack Bonus</h5>
              <p className="mt-2">
                {sheet.class_bonus.description}
              </p>
              <hr/>
              <dl className="mt-2">
                <dt>Type:</dt>
                <dd>{sheet.class_bonus.type}</dd>
                <dt>Base Chance:</dt>
                <dd>{sheet.class_bonus.base_chance.toFixed(2) * 100}%</dd>
                <dt>Requirements:</dt>
                <dd>{sheet.class_bonus.requires}</dd>
              </dl>
            </Col>
          </Row>
        </Card.Body>
      </Card>
    )
  }
}