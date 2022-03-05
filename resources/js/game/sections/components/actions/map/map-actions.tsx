import React, {Fragment} from "react";
import MapActionsProps from "../../../../lib/game/types/map/map-actions-props";
import PrimaryButton from "../../../../components/ui/buttons/primary-button";

export default class MapActions extends React.Component<MapActionsProps, any> {

    constructor(props: MapActionsProps) {
        super(props);
    }

    move(direction: string) {
    }

    render() {
        return (
            <Fragment>
                <div className='grid xl:grid-cols-2'>
                    <span>X/Y: 0/0</span>
                    <div className="mt-4 xl:mt-0">
                        <div className='grid grid-cols-3 gap-1'>
                            <span>Adventure</span>
                            <span className='text-center'>Set Sail</span>
                            <span>Teleport</span>
                        </div>
                    </div>
                </div>
                <div className='text-left mt-4 mb-3'>
                    Characters On Map: X | Plane Quests
                </div>
                <div className='mt-4 mb-4 border-b-2 border-b-gray-600 dark:border-b-gray-300'></div>
                <div className='grid gap-2 lg:grid-cols-4 lg:gap-4'>
                    <PrimaryButton button_label={'North'} on_click={() => this.move.bind('north')} />
                    <PrimaryButton button_label={'South'} on_click={() => this.move.bind('south')} />
                    <PrimaryButton button_label={'West'} on_click={() => this.move.bind('west')} />
                    <PrimaryButton button_label={'East'} on_click={() => this.move.bind('east')} />
                </div>
            </Fragment>
        )
    }
}
