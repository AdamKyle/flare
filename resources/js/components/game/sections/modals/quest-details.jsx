import React, {Fragment} from 'react';
import {Modal, Button, Tabs, Tab} from 'react-bootstrap';
import ItemName from "../../../marketboard/components/item-name";
import AlertInfo from "../../components/base/alert-info";

export default class QuestDetails extends React.Component {

  constructor(props) {
    super(props);
  }

  getNPCCommands(npc) {
    return npc.commands.map((command) => command.command).join(', ');
  }

  formatNumber(number) {
    return number.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  }

  renderPlaneAccessRequirements(map) {
    if (map.map_required_item !== null) {
      return (
        <Fragment>
          <dt>Required to access</dt>
          <dd><ItemName item={map.map_required_item} /></dd>

          {
            map.map_required_item.required_quest !== null ?
              <Fragment>
                <dt>Which needs you to complete (Quest)</dt>
                <dd>{map.map_required_item.required_quest.name}</dd>
                <dt>By Speaking to</dt>
                <dd>{map.map_required_item.required_quest.npc.real_name}</dd>
                <dt>Who is at (X/Y)</dt>
                <dd>{map.map_required_item.required_quest.npc.x_position}/{map.map_required_item.required_quest.npc.y_position}</dd>
                <dt>On plane</dt>
                <dd>{map.map_required_item.required_quest.npc.game_map.name}</dd>
                {this.renderPlaneAccessRequirements(map.map_required_item.required_quest.npc.game_map)}
              </Fragment>
            : null
          }

          {
            map.map_required_item.required_monster !== null ?
              <Fragment>
                <dt>Which requires you to fight (first)</dt>
                <dd>{map.map_required_item.required_monster.name}</dd>
                <dt>Who resides on plane</dt>
                <dd>{map.map_required_item.required_monster.game_map.name}</dd>
                {this.renderPlaneAccessRequirements(map.map_required_item.required_monster.game_map)}
              </Fragment>
            : null
          }
        </Fragment>
      );
    }

    return null;
  }

  renderLocations(locations) {
    return locations.map((location) => {
      return  <Fragment>
        <dl>
          <dt>By Going to</dt>
          <dd>{location.name}</dd>
          <dt>Which is at (X/Y)</dt>
          <dd>{location.x}/{location.y}</dd>
          <dt>On Plane</dt>
          <dd>{location.map.name}</dd>
          {this.renderPlaneAccessRequirements(location.map)}
        </dl>
      </Fragment>
    });
  }

  renderItem(item) {
    return (
      <Fragment>
        {
          item.drop_location_id !== null ?
            <AlertInfo icon={'fas fa-question-circle'} title={"Special Locations"}>
              <p>Some items, such as this one, only drop when you are at a special location. These locations
                increase enemy strength making them more of a challenge.</p>
              <p>These items have a 1/1,000,000 chance to drop. Your looting skill is capped at 45% here.</p>
              <p>
                <strong>These items will not drop if automation is detected. You must manually farm these quest items.</strong>
              </p>
            </AlertInfo>
            : null
        }
        {
          item.required_monster !== null ?
            item.required_monster.is_celestial_entity ?
              <AlertInfo icon={'fas fa-question-circle'} title={"Celestial Fight is required"}>
                <p>
                  Some quests such as this one may have you fighting a Celestial entity. You can check the <a href="/information/npcs" target="_blank">help docs (NPC's)</a> to find out, based on which plane,
                  which Summoning NPC you ned to speak to inorder to conjure the entity, there is only one per plane.
                </p>
                <p>
                  Celestial Entities below Dungeons plane, will not be included in the weekly spawn.
                </p>
              </AlertInfo>
            : null
          : null
        }
        <dl>
          {
            item.required_monster !== null ?
              <Fragment>
                <dt>Obtained by killing</dt>
                <dd>{item.required_monster.name} {item.required_monster.is_celestial_entity ? "(Celestial)" : "(Regular Monster)"}</dd>
                <dt>Resides on plane</dt>
                <dd>{item.required_monster.game_map.name}</dd>
                {this.renderPlaneAccessRequirements(item.required_monster.game_map)}
              </Fragment>
              : null
          }

          {
            item.required_quest !== null ?
              <Fragment>
                <dt>Obtained by completing</dt>
                <dd>{item.required_quest.name}</dd>
                <dt>Which belongs to (NPC)</dt>
                <dd>{item.required_quest.npc.real_name}</dd>
                <dt>Who is on the plane of</dt>
                <dd>{item.required_quest.npc.game_map.name}</dd>
                <dt>At coordinates (X/Y)</dt>
                <dd>{item.required_quest.npc.x_position} / {item.required_quest.npc.y_position}</dd>
                {this.renderPlaneAccessRequirements(item.required_quest.npc.game_map)}
              </Fragment>
              : null
          }

          {
            item.drop_location_id !== null ?
              <Fragment>
                <dt>By Visiting (Fighting monsters for it to drop)</dt>
                <dd>{item.drop_location.name}</dd>
                <dt>At coordinates (X/Y)</dt>
                <dd>{item.drop_location.x} / {item.drop_location.y}</dd>
                <dt>Which is on the plane</dt>
                <dd>{item.drop_location.map.name}</dd>
                {this.renderPlaneAccessRequirements(item.drop_location.map)}
              </Fragment>
              : null
          }
        </dl>
        {
          item.locations.length > 0 ?
            <Fragment>
              <hr />
              <h3 className="tw-font-light">Locations</h3>
              <p>Locations that will give you the item, just for visiting.</p>
              <hr />
              {this.renderLocations(item.locations)}
            </Fragment>
            : null
        }
      </Fragment>
    )
  }

  render() {
    console.log(this.props.quest);
    const npcPLaneAccess = this.renderPlaneAccessRequirements(this.props.quest.npc.game_map);

    return (
      <>
        <Modal show={this.props.show} onHide={this.props.questDetailsClose} dialogClassName="large-modal">
          <Modal.Header closeButton>
            <Modal.Title>{this.props.quest.name}</Modal.Title>
          </Modal.Header>
          <Modal.Body>
            <Tabs defaultActiveKey="npc-info" id="map-quest">
              <Tab eventKey="npc-info" title="NPC Info">
                <div className="mt-3">
                  <div className="row">
                    <div className={npcPLaneAccess !== null ? 'col-md-6' : 'col-md-12'}>
                      <h5 className="tw-font-light">NPC Details</h5>
                      <hr />
                      <dl>
                        <dt>Name</dt>
                        <dd>{this.props.quest.npc.name}</dd>
                        <dt>How to message</dt>
                        <dd><code>{this.props.quest.npc.text_command_to_message}</code></dd>
                        <dt>Available Commands</dt>
                        <dd>{this.getNPCCommands(this.props.quest.npc)}</dd>
                        <dt>Coordinates (X/Y)</dt>
                        <dd>{this.props.quest.npc.x_position} / {this.props.quest.npc.y_position}</dd>
                        <dt>On Plane</dt>
                        <dd>{this.props.quest.npc.game_map.name}</dd>
                        <dt>Must be at same location?</dt>
                        <dd>{this.props.quest.npc.must_be_at_same_location ? 'Yes' : 'No'}</dd>
                      </dl>
                      <hr />
                      <h3 className="tw-font-light">Info</h3>
                      <p>
                        Use the chat box to communicate with the NPC by private messaging them. The Available Commands section outlines accepted commands you can
                        message them. Some NPC's might require you to be at their location. This means you have to physically be at the same coordinates before
                        you message the npc to complete the quest.
                      </p>
                    </div>
                    <div className={npcPLaneAccess !== null ? 'col-md-6' : 'hide'}>
                      <h5 className="tw-font-light">How to access this NPC</h5>
                      <hr />
                      <dl>
                        {npcPLaneAccess}
                      </dl>
                    </div>
                  </div>


                </div>
              </Tab>
              <Tab eventKey="required-info" title="Required to complete">
                <p className="mt-3">
                  Below you will find all the requirements of this quest. Once you have met them, make sure to check the NPC
                  tab to see where to go and what command to use to interact with them.
                </p>
                <p>
                  If this NPC only accepts currency, you should probably do the quests in order,
                  specially if they have another quest where the currency is the same and of higher requirement.
                </p>
                <hr />
                <div className="mt-3">
                  <Tabs defaultActiveKey="base-required-info" id="quest-info">
                    <Tab eventKey="base-required-info" title="Base Requirements">
                      <dl className="mt-3">
                        {
                          this.props.quest.gold_cost !== null ?
                            <Fragment>
                              <dt>Gold Cost:</dt>
                              <dd>{this.formatNumber(this.props.quest.gold_cost)}</dd>
                            </Fragment>
                            :
                            null
                        }
                        {
                          this.props.quest.gold_dust_cost !== null ?
                            <Fragment>
                              <dt>Gold Dust Cost:</dt>
                              <dd>{this.formatNumber(this.props.quest.gold_dust_cost)}</dd>
                            </Fragment>
                            :
                            null
                        }
                        {
                          this.props.quest.shard_cost !== null ?
                            <Fragment>
                              <dt>Shards Cost:</dt>
                              <dd>{this.formatNumber(this.props.quest.shard_cost)}</dd>
                            </Fragment>
                            :
                            null
                        }
                        {
                          this.props.quest.item_id !== null ?
                            <Fragment>
                              <dt>Required Item:</dt>
                              <dd><ItemName item={this.props.quest.item} /></dd>
                            </Fragment>
                            :
                            null
                        }
                        {
                          this.props.quest.secondary_required_quest_item !== null ?
                            <Fragment>
                              <dt>Secondary Required Item:</dt>
                              <dd><ItemName item={this.props.quest.secondary_required_quest_item} /></dd>
                            </Fragment>
                            :
                            null
                        }
                        {
                          this.props.quest.access_to_map_id !== null ?
                            <Fragment>
                              <dt>Plane Access Required:</dt>
                              <dd>{this.props.quest.required_plane.name}</dd>
                            </Fragment>
                          : null
                        }
                        {
                          this.props.quest.faction_game_map_id !== null ?
                            <Fragment>
                              <dt>Plane Faction Name (Map to fight on)</dt>
                              <dd>{this.props.quest.faction_map.name}</dd>
                              <dt>Level required</dt>
                              <dd>{this.props.quest.required_faction_level}</dd>
                            </Fragment>
                          : null
                        }
                      </dl>
                    </Tab>
                    {
                      this.props.quest.item_id !== null ?
                        <Tab eventKey="required-item" title="Item Requirements">
                          <h3 className="tw-font-light mt-3">Quest Requires Item: {<ItemName item={this.props.quest.item} />}</h3>
                          <hr />
                          <p>This quest requires you to hand in item. Below you will find relevant details as to how to obtain the item
                            you need.</p>
                          {this.renderItem(this.props.quest.item)}
                        </Tab>
                      : null
                    }
                    {
                      this.props.quest.secondary_required_quest_item !== null ?
                        <Tab eventKey="secondary-required-item" title="Secondary Item Requirements">
                          <h3 className="tw-font-light mt-3">Quest Secondary Item Requires Item: {<ItemName item={this.props.quest.secondary_required_quest_item} />}</h3>
                          <hr />
                          <p>
                            This quest requires a secondary item. Below you will find all the relevant details as to how to obtain the item
                            you need.
                          </p>
                          {this.renderItem(this.props.quest.secondary_required_quest_item)}
                        </Tab>
                        : null
                    }
                    {
                      this.props.quest.faction_game_map_id !== null ?
                        <Tab eventKey="faction-required" title="Faction Level">
                          <h3 className="tw-font-light mt-3">Quest Requires Specific Faction Level</h3>
                          <hr />
                          <p>
                            This quest requires you to have a Faction at a specific level to complete it.
                            To do this, use Auto battle on the map required to fight any monster.
                          </p>

                          <dl>
                            <dt>Plane Faction Name (Map to fight on)</dt>
                            <dd>{this.props.quest.faction_map.name}</dd>
                            <dt>Level required</dt>
                            <dd>{this.props.quest.required_faction_level}</dd>
                            {this.renderPlaneAccessRequirements(this.props.quest.faction_map)}
                          </dl>
                        </Tab>
                        : null
                    }
                    {
                      this.props.quest.access_to_map_id !== null ?
                        <Tab eventKey="plane-access-required" title="Plane Access">
                          <h3 className="tw-font-light mt-3">Quest Requires Plane Access</h3>
                          <hr />
                          <p>This quest requires that you have access to a specific plane. You can find the relevant details of the quest that would get you access.
                            There may be other quests involved, so make sure to check that quest out to see whats required.</p>

                          <dl>
                            {this.renderPlaneAccessRequirements(this.props.quest.required_plane)}
                          </dl>
                        </Tab>
                        : null
                    }
                  </Tabs>
                </div>
              </Tab>
              <Tab eventKey="reward-info" title="Reward">
                <div className="mt-3">
                  <p>Upon completing this quest, buy speaking to the NPC and entering the command, once you have
                  met the required objectives you will be rewarded with the following.</p>
                  <hr />
                  <dl>
                    {
                      this.props.quest.reward_xp !== null ?
                        <Fragment>
                          <dt>XP Reward</dt>
                          <dd>{this.formatNumber(this.props.quest.reward_xp)}</dd>
                        </Fragment>
                      : null
                    }
                    {
                      this.props.quest.reward_gold !== null ?
                        <Fragment>
                          <dt>Gold Reward</dt>
                          <dd>{this.formatNumber(this.props.quest.reward_gold)}</dd>
                        </Fragment>
                        : null
                    }
                    {
                      this.props.quest.reward_gold_dust !== null ?
                        <Fragment>
                          <dt>Gold Dust Reward</dt>
                          <dd>{this.formatNumber(this.props.quest.reward_gold_dust)}</dd>
                        </Fragment>
                        : null
                    }
                    {
                      this.props.quest.reward_shards !== null ?
                        <Fragment>
                          <dt>Shards Reward</dt>
                          <dd>{this.formatNumber(this.props.quest.reward_shards)}</dd>
                        </Fragment>
                        : null
                    }
                    {
                      this.props.quest.unlocks_skill ?
                        <Fragment>
                          <dt>Unlocks New Skill</dt>
                          <dd>{this.props.quest.unlocks_skill_name}</dd>
                        </Fragment>
                        : null
                    }
                    {
                      this.props.quest.reward_item !== null ?
                        <Fragment>
                          <dt>Item reward</dt>
                          <dd>
                            <a href={"/items/" + this.props.quest.reward_item.id} target="_blank">
                              <ItemName item={this.props.quest.reward_item} /> <i
                              className="fas fa-external-link-alt"></i>
                            </a>
                          </dd>
                        </Fragment>
                        : null
                    }
                  </dl>
                </div>
              </Tab>
            </Tabs>
          </Modal.Body>
          <Modal.Footer>
            <Button variant="secondary" onClick={this.props.questDetailsClose}>
              Close
            </Button>
          </Modal.Footer>
        </Modal>
      </>
    );
  }
}
