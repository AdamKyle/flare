import React, {Fragment} from 'react';
import LockedLocationType from "../../game/sections/lib/LockedLocationType";

export default class BaseDetails extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    return (
      <>
        <div dangerouslySetInnerHTML={{__html: this.props.item.description}}>

        </div>
        <dl className="mt-3">
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
          {
            this.props.item.holy_level !== null ?
              <Fragment>
                <dt><strong>Holy Item level</strong>:</dt>
                <dd>{this.props.item.holy_level}</dd>
                <dt><strong>Stat Range %</strong>:</dt>
                <dd>{LockedLocationType.getEffect(this.props.item.holy_level).stat_bonus_increase}</dd>
                <dt><strong>Devouring Darkness Range %</strong>:</dt>
                <dd>{LockedLocationType.getEffect(this.props.item.holy_level).devouring_darkness_range}</dd>
              </Fragment>
            : null
          }
        </dl>
      </>
    )
  }
}
