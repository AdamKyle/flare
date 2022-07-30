import React, {Fragment} from "react";
import MovePlayer from "../../../lib/game/map/ajax/move-player";
import PrimaryOutlineButton from "../../../components/ui/buttons/primary-outline-button";
import TraverseModal from "../../components/actions/modals/traverse-modal";
import DirectionalMovementProps from "../../../lib/game/map/types/directional-movement-props";
import DirectionalMovementState from "../../../lib/game/map/types/directional-movement-state";

export default class DirectionalMovement extends React.Component<DirectionalMovementProps, DirectionalMovementState> {

    constructor(props: DirectionalMovementProps) {
        super(props);

        this.state = {
            is_movement_disabled: false,
            show_traverse: false,
        }
    }

    move(direction: string) {
        this.setState({
            is_movement_disabled: true,
        }, () => {
            this.handleMovePlayer(direction);
        });
    }

    traverse() {
        this.setState({
            show_traverse: !this.state.show_traverse
        })
    }

    handleMovePlayer(direction: string) {
        (new MovePlayer(this)).setCharacterPosition(this.props.character_position)
            .setMapPosition(this.props.map_position)
            .movePlayer(this.props.character_id, direction, this);
    }

    render() {
        return (
            <Fragment>
                <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block'></div>
                <div className='grid gap-2 lg:grid-cols-5 lg:gap-4'>
                    <PrimaryOutlineButton disabled={this.state.is_movement_disabled || this.props.is_dead}
                                   button_label={'North'}
                                   on_click={() => this.move('north')}
                    />
                    <PrimaryOutlineButton disabled={this.state.is_movement_disabled || this.props.is_dead}
                                   button_label={'South'}
                                   on_click={() => this.move('south')}
                    />
                    <PrimaryOutlineButton disabled={this.state.is_movement_disabled || this.props.is_dead}
                                   button_label={'West'}
                                   on_click={() => this.move('west')}
                    />
                    <PrimaryOutlineButton disabled={this.state.is_movement_disabled || this.props.is_dead}
                                   button_label={'East'}
                                   on_click={() => this.move('east')}
                    />
                    <PrimaryOutlineButton disabled={this.state.is_movement_disabled || this.props.is_dead || this.props.is_automation_running}
                                   button_label={'Traverse'}
                                   on_click={() => this.traverse()}
                    />
                </div>

                {
                    this.state.show_traverse ?
                        <TraverseModal
                            is_open={true}
                            handle_close={this.traverse.bind(this)}
                            character_id={this.props.character_id}
                            map_id={this.props.map_id}
                        />
                    : null
                }
            </Fragment>
        )
    }
}
