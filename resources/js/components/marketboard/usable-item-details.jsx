import React from 'react';
import {Card} from 'react-bootstrap';

export default class UsableItemDetails extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    console.log(this.props);
    return (
      <div className="mb-3">
        <Card>
          <Card.Body>
            <h3>Item Details</h3>
            <hr />
            {
              this.props.item.damages_kingdoms ?
                <>
                  <dl>
                    <dt>Damages Kingdom For:</dt>
                    <dd>{(this.props.item.kingdom_damage * 100).toFixed(0)}%</dd>
                  </dl>
                </>
              :
                this.props.item.stat_increase ?
                  <>
                    <dl>
                      <dd>Increases all core stats by:</dd>
                      <dt>{(this.props.item.increase_stat_by * 100).toFixed(0)}%</dt>
                    </dl>
                  </>
                :
                  <>
                    <dl>
                      <dt>Skills Affected: </dt>
                      <dd>{this.props.item.affects_skills.join(', ')}</dd>
                      <dt>Skill Bonus: </dt>
                      <dd>{(this.props.item.increase_skill_bonus_by * 100)}%</dd>
                      <dt>Skill Training Bonus: </dt>
                      <dd>{(this.props.item.increase_skill_training_bonus_by * 100)}%</dd>
                      <dt>Base Damage Mod Bonus:</dt>
                      <dd>{(this.props.item.base_damage_mod_bonus * 100)}%</dd>
                      <dt>Base Healing Mod Bonus:</dt>
                      <dd>{(this.props.item.base_healing_mod_bonus * 100)}%</dd>
                      <dt>Base AC Mod Bonus:</dt>
                      <dd>{(this.props.item.base_ac_mod_bonus * 100)}%</dd>
                      <dt>Fight Timeout Mod Bonus:</dt>
                      <dd>{(this.props.item.fight_time_out_mod_bonus * 100)}%</dd>
                      <dt>Move Timeout Mod Bonus:</dt>
                      <dd>{(this.props.item.move_time_out_mod_bonus)}%</dd>
                    </dl>
                  </>

            }
          </Card.Body>
        </Card>
      </div>
    )
  }
}
