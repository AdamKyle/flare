import React, { Fragment } from "react";
import ActionSectionProps from "./types/action-section-props";
import SmallerActions from "./smaller-actions";
import Actions from "./actions";

export default class ActionSection extends React.Component<
    ActionSectionProps,
    {}
> {
    constructor(props: ActionSectionProps) {
        super(props);
    }

    render() {
        return (
            <Fragment>
                {this.props.view_port <= 1024 ? (
                    <SmallerActions
                        character={this.props.character}
                        character_status={this.props.character_status}
                        character_position={this.props.character_position}
                        character_currencies={this.props.character_currencies}
                        celestial_id={this.props.celestial_id}
                        update_celestial={this.props.update_celestial}
                        update_plane_quests={this.props.update_plane_quests}
                        update_character_position={
                            this.props.update_character_position
                        }
                        view_port={this.props.view_port}
                        can_engage_celestial={
                            this.props.character.can_engage_celestials
                        }
                        action_data={this.props.action_data}
                        map_data={this.props.map_data}
                        update_parent_state={this.props.update_parent_state}
                        set_map_data={this.props.set_map_data}
                        fame_tasks={this.props.fame_tasks}
                        update_show_map_mobile={
                            this.props.update_show_map_mobile
                        }
                    />
                ) : (
                    <Actions
                        character={this.props.character}
                        character_status={this.props.character_status}
                        character_position={this.props.character_position}
                        celestial_id={this.props.celestial_id}
                        update_celestial={this.props.update_celestial}
                        can_engage_celestial={
                            this.props.character.can_engage_celestials
                        }
                        action_data={this.props.action_data}
                        update_parent_state={this.props.update_parent_state}
                        fame_tasks={this.props.fame_tasks}
                        update_show_map_mobile={
                            this.props.update_show_map_mobile
                        }
                    />
                )}
            </Fragment>
        );
    }
}
