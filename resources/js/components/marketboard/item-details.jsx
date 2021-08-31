import React from 'react';
import {Accordion, Card, Button, Row, Col} from 'react-bootstrap';

export default class ItemDetails extends React.Component {

  constructor(props) {
    super(props)
  }

  formatFloat(float) {
    return float.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")
  }

  renderAffixDetails(type) {
    const item = this.props.item['item_' + type];

    return (
      <>
        <h5 className="mb-2">{type.charAt(0).toUpperCase() + type.slice(1)}</h5>
        <p>{this.props.item['item_' + type].description}</p>
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
    );
  }

  renderAffixes() {
    if (this.props.item.item_prefix === null && this.props.item.item_suffix === null) {
      return (
        <div className="alert alert-info">
          There are no affixes on this this.props.item.
        </div>
      );
    }

    let colSize = 12;

    if (this.props.item.item_prefix !== null && this.props.item.item_suffix !== null) {
      return (
        <>
          <Col xs={6}>
            <Card>
              <Card.Body>
                {this.renderAffixDetails('prefix')}
              </Card.Body>
            </Card>
          </Col>
          <Col xs={6}>
            <Card>
              <Card.Body>
                {this.renderAffixDetails('suffix')}
              </Card.Body>
            </Card>
          </Col>
        </>
      );
    }

    if (this.props.item.item_prefix !== null) {
      return (
        <>
          <Col xs={12}>
            <Card>
              <Card.Body>
                {this.renderAffixDetails('prefix')}
              </Card.Body>
            </Card>
          </Col>
        </>
      );
    }

    if (this.props.item.item_suffix !== null) {
      return (
        <>
            <Col xs={12}>
              <Card>
                <Card.Body>
                  {this.renderAffixDetails('suffix')}
                </Card.Body>
              </Card>
            </Col>
        </>
      );
    }
  }

  render() {
    console.log(this.props.item);
    return (
      <Accordion>
        <Card>
          <Card.Header>
            <Accordion.Toggle as={Button} variant="link" eventKey="0">
              Item Details
            </Accordion.Toggle>
          </Card.Header>
          <Accordion.Collapse eventKey="0">
            <Card.Body>
              <div className="alert alert-info mb-3">
                Values include (any) attached affixes.
              </div>
              <Row>
                <Col xs={6}>
                  <h3>Item Details</h3>
                  <Card>
                    <Card.Body>
                      <dl>
                        <dt><strong>Base Damage</strong>:</dt>
                        <dd>{this.props.item.base_damage} (With all modifiers)</dd>
                        <dt><strong>Base AC</strong>:</dt>
                        <dd>{this.props.item.base_ac} (With all modifiers)</dd>
                        <dt><strong>Base Healing</strong>:</dt>
                        <dd>{this.props.item.base_healing} (With all modifiers)</dd>
                        <dt><strong>Type</strong>:</dt>
                        <dd>{this.props.item.type}</dd>
                      </dl>
                      <hr />
                      <h4>Crafting Details</h4>
                      <dl>
                        <dt><strong>Crafting Type</strong>:</dt>
                        <dd>{this.props.item.crafting_type}</dd>
                        <dt><strong>Skill Level Required</strong>:</dt>
                        <dd>{this.props.item.skill_level_req}</dd>
                        <dt><strong>Skill Level Trivial</strong>:</dt>
                        <dd>{this.props.item.skill_level_trivial}</dd>
                      </dl>
                      <hr />
                    </Card.Body>
                  </Card>
                </Col>
                <Col xs={6}>
                  <h3>Base Equip Stats</h3>
                  <Card>
                    <Card.Body>
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
                    </Card.Body>
                  </Card>
                </Col>
              </Row>
              <hr />
              <h3>Item Affixes</h3>
              <Row>
                {this.renderAffixes()}
              </Row>
            </Card.Body>
          </Accordion.Collapse>
        </Card>
      </Accordion>
    );
  }
}
