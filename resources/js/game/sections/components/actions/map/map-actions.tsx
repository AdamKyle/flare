import React, {Fragment} from "react";
import MapActionsProps from "../../../../lib/game/types/map/map-actions-props";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";
import SuccessOutlineButton from "../../../../components/ui/buttons/success-outline-button";

export default class MapActions extends React.Component<MapActionsProps, any> {

    constructor(props: MapActionsProps) {
        super(props);
    }

    move(direction: string) {
        this.props.move_player(direction);
    }

    adventure() {

    }

    setSail() {

    }

    teleport() {

    }

    render() {
        return (
            <Fragment>
                <div className='grid xl:grid-cols-2'>
                    <span>X/Y: 0/0</span>
                    <div className="mt-4 xl:mr-[20px] xl:mt-0">
                        <div className='grid grid-cols-3 gap-1'>
                            <SuccessOutlineButton additional_css={'text-center'} button_label={'Adventure'} on_click={this.adventure.bind(this)} />
                            <SuccessOutlineButton additional_css={'text-center'} button_label={'Set Sail'} on_click={this.setSail.bind(this)} />
                            <SuccessOutlineButton additional_css={'text-center'} button_label={'Teleport'} on_click={this.teleport.bind(this)} />
                        </div>
                    </div>
                </div>
                <div className='text-left mt-4 mb-3'>
                    Characters On Map: X | Plane Quests
                </div>
                <div className='mt-4 mb-4 border-b-2 border-b-gray-600 dark:border-b-gray-300'></div>
                <div className='grid gap-2 lg:grid-cols-4 lg:gap-4'>
                    <PrimaryButton button_label={'North'} on_click={() => this.move('north')} />
                    <PrimaryButton button_label={'South'} on_click={() => this.move('south')} />
                    <PrimaryButton button_label={'West'} on_click={() => this.move('west')} />
                    <PrimaryButton button_label={'East'} on_click={() => this.move('east')} />
                </div>
            </Fragment>
        )
    }
}
