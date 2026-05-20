import React, { Fragment } from "react";
import MovePlayer from "../lib/ajax/move-player";
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import TraverseModal from "../modals/traverse-modal";
import DirectionalMovementProps from "../types/directional-movement-props";
import DirectionalMovementState from "../types/directional-movement-state";
import LocationDetails from "../types/location-details";

export default class DirectionalMovement extends React.Component<
    DirectionalMovementProps,
    DirectionalMovementState
> {
    constructor(props: DirectionalMovementProps) {
        super(props);

        this.state = {
            show_traverse: false,
        };
    }

    move(direction: string) {
        this.handleMovePlayer(direction);
    }

    traverse() {
        this.setState({
            show_traverse: !this.state.show_traverse,
        });
    }

    closeTraverse() {
        this.setState({
            show_traverse: false,
        });
    }

    handleMovePlayer(direction: string) {
        new MovePlayer(this)
            .setCharacterPosition(this.props.character_position)
            .setMapPosition(this.props.map_position)
            .movePlayer(this.props.character_id, direction, this);
    }

    isAtSpecialLocation(): boolean {
        if (this.props.locations === null) {
            return false;
        }

        const locations = this.props.locations.filter(
            (location: LocationDetails) =>
                location.x === this.props.character_position.x &&
                location.y === this.props.character_position.y,
        );

        if (locations.length === 0) {
            return false;
        }

        return (
            locations[0].type !== null ||
            locations[0].enemy_strength_type !== null
        );
    }

    isMovementDisabled(): boolean {
        return (
            !this.props.can_move ||
            this.props.is_dead ||
            this.props.is_delve_running ||
            (this.props.is_automation_running && this.isAtSpecialLocation())
        );
    }

    render() {
        return (
            <Fragment>
                <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block"></div>
                <div className="grid gap-2 lg:grid-cols-5 gap-4">
                    <PrimaryOutlineButton
                        disabled={this.isMovementDisabled()}
                        button_label={"North"}
                        on_click={() => this.move("north")}
                    />
                    <PrimaryOutlineButton
                        disabled={this.isMovementDisabled()}
                        button_label={"South"}
                        on_click={() => this.move("south")}
                    />
                    <PrimaryOutlineButton
                        disabled={this.isMovementDisabled()}
                        button_label={"West"}
                        on_click={() => this.move("west")}
                    />
                    <PrimaryOutlineButton
                        disabled={this.isMovementDisabled()}
                        button_label={"East"}
                        on_click={() => this.move("east")}
                    />
                    <PrimaryOutlineButton
                        disabled={
                            !this.props.can_move ||
                            this.props.is_dead ||
                            this.props.is_automation_running ||
                            this.props.is_delve_running
                        }
                        button_label={"Traverse"}
                        on_click={() => this.traverse()}
                    />
                </div>

                {this.state.show_traverse ? (
                    <TraverseModal
                        is_open={true}
                        handle_close={this.closeTraverse.bind(this)}
                        character_id={this.props.character_id}
                        map_id={this.props.map_id}
                        update_map_state={this.props.update_map_state}
                    />
                ) : null}
            </Fragment>
        );
    }
}
