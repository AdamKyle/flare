import React, {Fragment} from "react";
import MapActionsProps from "../../../../lib/game/types/map/map-actions-props";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import SuccessOutlineButton from "../../../../components/ui/buttons/success-outline-button";
import MapActionsState from "../../../../lib/game/types/map/map-actions-state";
import PrimaryOutlineButton from "../../../../components/ui/buttons/primary-outline-button";

export default class MapActions extends React.Component<MapActionsProps, MapActionsState> {

    constructor(props: MapActionsProps) {
        super(props);

        this.state = {
            is_movement_disabled: false,
        }
    }

    componentDidUpdate(prevProps: Readonly<MapActionsProps>, prevState: Readonly<MapActionsState>, snapshot?: any) {
        if (this.props.can_player_move && this.state.is_movement_disabled) {
            this.setState({is_movement_disabled: false});
        }

        if (!this.props.can_player_move && !this.state.is_movement_disabled) {
            this.setState({is_movement_disabled: true});
        }
    }

    move(direction: string) {
        this.setState({
            is_movement_disabled: true,
        }, () => {
            this.props.move_player(direction);
        })
    }

    adventure() {

    }

    setSail() {

    }

    teleport() {

    }

    openPlaneQuests() {

    }

    render() {
        return (
            <Fragment>
                <div className='grid xl:grid-cols-2'>
                    <span>X/Y: 0/0</span>
                    <div className="mt-4 xl:mr-[20px] xl:mt-0">
                        <div className='grid grid-cols-3 gap-1'>
                            <SuccessOutlineButton additional_css={'text-center px-0'} button_label={'Adventure'} on_click={this.adventure.bind(this)} />
                            <SuccessOutlineButton additional_css={'text-center'} button_label={'Set Sail'} on_click={this.setSail.bind(this)} />
                            <SuccessOutlineButton additional_css={'text-center'} button_label={'Teleport'} on_click={this.teleport.bind(this)} />
                        </div>
                    </div>
                </div>
                <div className='text-left mt-4 mb-3'>
                    Characters On Map: {this.props.players_on_map} | <PrimaryOutlineButton additional_css={'text-center'} button_label={'Plane Quests'} on_click={this.openPlaneQuests.bind(this)} />
                </div>
                <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 hidden sm:block'></div>
                <div className='grid gap-2 lg:grid-cols-4 lg:gap-4'>
                    <PrimaryButton disabled={this.state.is_movement_disabled} button_label={'North'} on_click={() => this.move('north')} />
                    <PrimaryButton disabled={this.state.is_movement_disabled} button_label={'South'} on_click={() => this.move('south')} />
                    <PrimaryButton disabled={this.state.is_movement_disabled} button_label={'West'} on_click={() => this.move('west')} />
                    <PrimaryButton disabled={this.state.is_movement_disabled} button_label={'East'} on_click={() => this.move('east')} />
                </div>
            </Fragment>
        )
    }
}
