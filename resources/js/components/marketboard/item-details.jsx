import React from 'react';
import {Accordion, Card, Button} from 'react-bootstrap';

export default class ItemDetails extends React.Component {

  constructor(props) {
    super(props)
  }

  getEffect(item) {
    switch (item.effect) {
      case ('walk-on-water'):
        return 'Walk on water';
      default:
        return 'N/A'
    }
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
          <dd>{item.base_damage_mod !== null ? item.base_damage_mod.toFixed() * 100 : 0}%</dd>
          <dt>Base AC Modifier:</dt>
          <dd>{item.base_ac_mod !== null ? item.base_ac_mod.toFixed() * 100 : 0}%</dd>
          <dt>Base Healing Modifier:</dt>
          <dd>{item.base_healing_mod !== null ? item.base_healing_mod.toFixed() * 100 : 0}%</dd>
          <dt>Str Modifier:</dt>
          <dd>{item.str_mod !== null ? item.str_mod.toFixed() * 100 : 0}%</dd>
          <dt>Dex Modifier:</dt>
          <dd>{item.dex_mod !== null ? item.dex_mod.toFixed() * 100 : 0}%</dd>
          <dt>Dur Modifier:</dt>
          <dd>{item.dur_mod !== null ? item.dur_mod.toFixed() * 100 : 0}%</dd>
          <dt>Int Modifier:</dt>
          <dd>{item.int_mod !== null ? item.int_mod.toFixed() * 100 : 0}%</dd>
          <dt>Chr Modifier:</dt>
          <dd>{item.chr_mod !== null ? item.chr_mod.toFixed() * 100 : 0}%</dd>
          <dt>Skill Name:</dt>
          <dd>{item.skill_name === null ? 'N/A' : this.props.item['item_' + type].skill_name}</dd>
          <dt>Skill Bonus:</dt>
          <dd>{item.skill_name === null ? 0 : this.props.item['item_' + type].skill_bonus.toFixed() * 100}%</dd>
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

    if (this.props.item.item_prefix !== null) {
      return this.renderAffixDetails('prefix');
    }

    if (this.props.item.item_suffix !== null) {
      return this.renderAffixDetails('suffix');
    }
  }

  render() {
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
              <div className="row mb-3">
                <div className="col-md-12">
                  <h3 className="mb-2">Item Details</h3>
                  <dl>
                    <dt>Base Damage:</dt>
                    <dd>{this.props.item.base_damage} <em>(With all modifiers)</em></dd>
                    <dt>Base AC:</dt>
                    <dd>{this.props.item.base_ac} <em>(With all modifiers)</em></dd>
                    <dt>Base Healing:</dt>
                    <dd>{this.props.item.base_healing} <em>(With all modifiers)</em></dd>
                    <dt>Type:</dt>
                    <dd>{this.props.item.type}</dd>
                    <dt>Effect:</dt>
                    <dd>
                      {this.getEffect(this.props.item)}
                    </dd>

                    {
                      this.props.item.type === 'quest' ?
                        <>
                          <dt>Affects Skill Name:</dt>
                          <dd>{this.props.item.skill_name === null ? 'N/A' : this.props.item.skill_name}</dd>
                          <dt>Bonus (XP) When Training:</dt>
                          <dd>{this.props.item.skill_training_bonus === null ? 'N/A' : this.props.item.skill_training_bonus * 100}%</dd>
                        </> : null
                    }
                  </dl>
                </div>
              </div>
              <hr/>
              <div className="row mb-3">
                <div className="col-md-12">
                  <h3 className="mb-2">Base Equip Stats</h3>
                  <p className="text-muted mb-2 mt-2" style={{fontSize: '12px', fontStyle: 'italic'}}>All values include
                    any attached affixes and any additional modifiers.</p>
                  <dl className="mt-2">
                    <dt>Attack:</dt>
                    <dd><span className='text-success'>{this.props.item.base_damage} </span></dd>
                    <dt>AC:</dt>
                    <dd><span className='text-success'>{this.props.item.base_ac} </span></dd>
                    <dt>Healing:</dt>
                    <dd><span className='text-success'>{this.props.item.base_healing} </span></dd>
                    <dt>Str Modifier:</dt>
                    <dd><span className='text-success'>{this.props.item.str_modifier * 100}% </span></dd>
                    <dt>Dur Modifier:</dt>
                    <dd><span className='text-success'>{this.props.item.dur_modifier * 100}% </span></dd>
                    <dt>Dex Modifier:</dt>
                    <dd><span className='text-success'>{this.props.item.dex_modifier * 100}% </span></dd>
                    <dt>Chr Modifier:</dt>
                    <dd><span className='text-success'>{this.props.item.chr_modifier * 100}% </span></dd>
                    <dt>Int Modifier:</dt>
                    <dd><span className='text-success'>{this.props.item.int_modifier * 100}% </span></dd>
                    <dt>Skill Name:</dt>
                    <dd>{this.props.item.skill_name !== null ? this.props.item.skill_nam : 'N/A'}</dd>
                    <dt>Skill Bonus:</dt>
                    <dd>{this.props.item.skill_training_bonus !== null ? this.props.item.skill_training_bonus * 100 + '%' : '0%'}</dd>
                  </dl>
                </div>
              </div>
              <hr/>
              <div className="row mb-3">
                <div className="col-md-12">
                  {this.renderAffixes()}
                </div>
              </div>
            </Card.Body>
          </Accordion.Collapse>
        </Card>
      </Accordion>
    );
  }
}
