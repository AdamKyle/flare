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
                    When you fight monsters at this location you can see their stats increase, but you can also see their
                    Percentage based values also increase. This refers to their Accuracy, Dodge, Devouring Light, resistances and more.
                  </p>
                  <p>
                    It is suggested that players have equipment that helps with Resistance Reduction and Stat Reduction <a href="/information/enchanting">enchantments</a>.
                    Players with out <a href="/information/voidance">Voidance or Devoidance</a> may find it harder to fight creatures here.
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
