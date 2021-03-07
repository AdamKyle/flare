import React   from 'react';
import isEmpty from'lodash/isEmpty';

export default class LocationInfo extends React.Component {
  constructor(props) {
    super(props);
  }

  adventures() {
    const adventures = [];
    
    this.props.location.adventures.forEach((adventure) => {
      adventures.push(
        <li key={adventure.id}>
          <a href={'/adeventures/' + adventure.id} target="_blank">{adventure.name} - Levels: {adventure.levels} at: {adventure.time_per_level} minutes per level.</a>
        </li>
      );
    });

    return adventures;
  }

  showAdventures() {
    if (!isEmpty(this.props.location.adventures)) {
      return (
        <>
          <hr />
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
          <hr />
          <h5>Quest Reward Items</h5>
          <div className="alert alert-info">Only handed out once, when you first visit the location.</div>
          <ul>
            <li>
              <a href={'/items/' + this.props.location.quest_reward_item.id} target="_blank">{this.props.location.quest_reward_item.name}</a>
            </li>
          </ul>
        </>
      )
    }
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
          {this.showAdventures()}
          {this.getRewards()}
        </div>
      </div>
    );
  }
}
