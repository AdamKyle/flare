import React from 'react';

export default class LocationInfo extends React.Component {
  constructor(props) {
    super(props);
  }

  adventures() {
    const adventures = [];
    console.log(this.props.location);
    this.props.location.adventures.forEach((adventure) => {
      adventures.push(
        <li key={adventure.id}>
          <a href={'/adeventures/' + adventure.id} target="_blank">{adventure.name} - Levels: {adventure.levels} at: {adventure.time_per_level} minutes per level.</a>
        </li>
      );
    });

    return adventures;
  }

  getRwards() {
    if (this.props.location.quest_reward_item !== null) {
      return (
        <>
          <h5>Quest Reward Items</h5>
          <div class="alert alert-info">Only handed out once, when you first visit the location.</div>
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
          <hr />
          <h5>Adventures</h5>
          <ul>
            {this.adventures()}
          </ul>
          <hr />
          {this.getRwards()}
        </div>
      </div>
    );
  }
}
