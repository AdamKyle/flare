import React from 'react';
import Card from "../components/templates/card";

export default class ConjureComponent extends React.Component {

  constructor(props) {
    super(props);
  }

  render() {
    return (
      <Card close={this.props.closeComponent} cardTitle="Conjure (NPC choice)">
        <div className="alert alert-info">
          <p>
            Below you may select which Celestial Entity you wish to have this npc conjure. <br />
            Celestial Entities are known for dropping Crystal Shards. Crystal Shards are used in Alchemy to craft powerful weapons for attacking kingdoms.
          </p>
          <p>To Conjure you must have both Gold <strong>and</strong> Gold Dust. Gold Dust is earned by you Disenchanting items or destroying them. Once you meet the requirements of the
          creature in the list cost wise, you can click "Conjure" and a global message will go out stating that you have conjured a Celestial Entity. These creatures spawn at random locations
          and if that location is a kingdom it has a specific percentage chance of dealing damage to the whole kingdom (including morale), which in it's self is a % of damage done.</p>
          <p>You must go to the location the Celestial Entity spawns at, to fight it. These fights are done server side with a 5 second timeout in between attacks, to allow other players a chance
          to attack.</p>
          <p>The first one to kill the Celestial Entity gets the reward (which will be seen as a both server and global message). There is an option to privately summon the Celestial Entity at twice the cost of the public summon.</p>
          <p>Celestial Entities are also <strong>much harder</strong> then regular critters, and have a one-out-of-a-million chance to spawn naturally by your character moving around the map (in all forms of movement).
          Players moving on one plane can cause a Celestial Entity to appear on another plane.</p>
          <p><strong>Conjuring Celestial Entities Can be done from any plane, for that plane - be it public or private.</strong></p>
        </div>
      </Card>
    )
  }
}
