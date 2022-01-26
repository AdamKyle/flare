import React, {Fragment} from 'react';
import isEmpty from 'lodash/isEmpty';
import AlertInfo from "../../../components/base/alert-info";

export default class LocationInfo extends React.Component {
  constructor(props) {
    super(props);
  }

  adventures() {
    const adventures = [];

    this.props.location.adventures.forEach((adventure) => {
      adventures.push(
        <li key={adventure.id}>
          <a href={'/adventures/' + adventure.id} target="_blank">{adventure.name} -
            Levels: {adventure.levels} at: {adventure.time_per_level} minutes per level.</a>
        </li>
      );
    });

    return adventures;
  }

  showAdventures() {
    if (!isEmpty(this.props.location.adventures)) {
      return (
        <>
          <hr/>
          <h5>Adventures</h5>
          <ul>
            {this.adventures()}
          </ul>
        </>
      )
    }

    return null;
  }

  getRewards() {
    if (this.props.location.quest_reward_item !== null) {
      return (
        <>
          <hr/>
          <h5>Quest Reward Items</h5>
          <div className="alert alert-info">Only handed out once, when you first visit the location.</div>
          <ul>
            <li>
              <a href={'/items/' + this.props.location.quest_reward_item.id}
                 target="_blank">{this.props.location.quest_reward_item.name}</a>
            </li>
          </ul>
        </>
      )
    }
  }

  formatNumber(number) {

    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  render() {
    const location = this.props.location;

    if (location == null) {
      return null;
    }

    return (
      <div>
        <div className="mb-2">
          <h5>Description</h5>
          <p>{location.description}</p>
          {
            location.enemy_strength_type !== null ?
              <Fragment>
                <dl className="mb-2">
                  <dt>Increase Core Stats By: </dt>
                  <dd>{this.formatNumber(location.increases_enemy_stats_by)}</dd>
                  <dt>Increase Percentage Based Values By: </dt>
                  <dd>{location.increase_enemy_percentage_by * 100}%</dd>
                </dl>
                <AlertInfo icon={"fas fa-question-circle"} title={"Attn!"}>
                  <p>
                    These locations contain the same monsters you have been fighting but their stats are increased and their percentage based stats such as skills
                    and resistances will also increased. Players will want stat and resistance reduction affixes in conjunction with class based affixes to deal enough damage but also
                    reduce the enemies resistances and stats so you can have a chance to hit them.
                  </p>
                  <p>
                    It is suggested that players have equipment that helps with Resistance Reduction and Stat Reduction <a href="/information/enchanting">enchantments</a>.
                    Players with out <a href="/information/voidance">Voidance or Devoidance</a> may find it harder to fight creatures here.
                  </p>
                  <p>
                    Special locations have a chance to drop specific quest items. You cannot auto battle to get these to drop, you must manually farm them. If auto battle is detected
                    in these places, you will not get the quest item. For these quest items, your looting chance is dropped to 45%. These items have a 1/1,000,000 chance to drop.
                    Having high looting makes the process easier.
                  </p>
                  <p>
                    Finally, if a special location is on a plane that increases the enemies strength, that is ALSO taken into consideration when fighting monsters at that location. For example if a plane increases
                    monster stats and percentage based attributes by 70%, and the special location increases stats by 1 Billion and 50% to Percentage, then all of that is added together when fighting in these locations.
                    <strong> Gear, level and stats all matter. Caution is advised.</strong>
                  </p>
                </AlertInfo>
              </Fragment>
            : null
          }
          {this.showAdventures()}
          {this.getRewards()}
        </div>
      </div>
    );
  }
}
