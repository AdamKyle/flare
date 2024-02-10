import React, {ReactNode} from "react";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import {KingdomQueueProps} from "./types/kingdom-queue-props";
import KingdomQueueState, {
    BuildingExpansionQueue,
    BuildingQueue,
    UnitMovementQueue,
    UnitQueue
} from "./types/kingdom-queue-state";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";

export default class KingdomQueues extends React.Component<KingdomQueueProps, KingdomQueueState> {

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            error_message: null,
            queues: null,
        }
    }

    componentDidMount() {
        console.log(
            this.props.kingdom_id,
            this.props.character_id,
        )
    }

    renderBuildingQueues(): ReactNode|ReactNode[]|[] {
        if (this.state.queues === null) {
            return <p className='my-2 italic'>Nothing Queued Up</p>
        }

        if (this.state.queues.building_queues.length === 0) {
            return <p className='my-2 italic'>Nothing Queued Up</p>
        }

        return this.state.queues.building_queues.map((buildingQueue: BuildingQueue) => {

            if (buildingQueue.type === 'upgrading') {
                return (
                    <div className='my-4'>
                        <div className='bold my-2'>
                            Upgrading {buildingQueue.name}
                        </div>
                        <TimerProgressBar  time_out_label={
                            'From Level: ' + buildingQueue.from_level + ' To Level: ' + buildingQueue.to_level
                        } time_remaining={buildingQueue.time_remaining} />
                    </div>
                )
            }

            if (buildingQueue.type === 'repairing') {
                return (
                    <div className='my-4'>
                        <div className='bold my-2'>
                            Repairing {buildingQueue.name}
                        </div>
                        <TimerProgressBar time_out_label={'Repairing'} time_remaining={buildingQueue.time_remaining} />
                    </div>
                )
            }
        }).filter((buildingQueueData: ReactNode | undefined) => {
            return typeof buildingQueueData !== 'undefined'
        });
    }

    renderUnitRecruitmentQueues(): ReactNode|ReactNode[]|[] {
        if (this.state.queues === null) {
            return <p className='my-2 italic'>Nothing Queued Up</p>
        }

        if (this.state.queues.unit_recruitment_queues.length === 0) {
            return <p className='my-2 italic'>Nothing Queued Up</p>
        }

        return this.state.queues.unit_recruitment_queues.map((unitRecruitmentQueue: UnitQueue) => {
            return (
                <div className='my-4'>
                    <div className='bold my-2'>
                        Recruiting {unitRecruitmentQueue.name}
                    </div>
                    <TimerProgressBar time_out_label={
                        'Rectuiting: ' + unitRecruitmentQueue.recruit_amount
                    } time_remaining={unitRecruitmentQueue.time_remaining} />
                </div>
            )
        });
    }

    renderUnitMovementQueues(): ReactNode|ReactNode[]|[] {
        if (this.state.queues === null) {
            return <p className='my-2 italic'>Nothing Queued Up</p>
        }

        if (this.state.queues.unit_movement_queues.length === 0) {
            return <p className='my-2 italic'>Nothing Queued Up</p>
        }

        return this.state.queues.unit_movement_queues.map((unitMovementQueue: UnitMovementQueue) => {
            return (
                <div className='my-4'>
                    <div className='bold my-2'>
                        {unitMovementQueue.name} Are on the move!
                    </div>
                    <TimerProgressBar time_out_label={
                        'Units are in movement'
                    } time_remaining={unitMovementQueue.time_remaining} />
                </div>
            )
        });
    }

    renderBuildingExpansionQueues(): ReactNode|ReactNode[]|[] {
        if (this.state.queues === null) {
            return <p className='my-2 italic'>Nothing Queued Up</p>
        }

        if (this.state.queues.building_expansion_queues.length === 0) {
            return <p className='my-2 italic'>Nothing Queued Up</p>
        }

        return this.state.queues.building_expansion_queues.map((buildingExpansionQueue: BuildingExpansionQueue) => {
            return (
                <div className='my-4'>
                    <div className='bold my-2'>
                        {buildingExpansionQueue.name} Is expanding production
                    </div>
                    <TimerProgressBar time_out_label={
                        'From slot: ' + buildingExpansionQueue.from_slot + ' to slot: ' + buildingExpansionQueue.to_slot
                    } time_remaining={buildingExpansionQueue.time_remaining} />
                </div>
            )
        });
    }

    render() {
        return (
            <div>
                <p className='my-2'>
                    Below you will find the various queues. This could be building expansions,
                    repairs, upgrades, unit recruitment and movement.
                </p>
                <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3'></div>
                {
                    this.state.loading ?
                        <LoadingProgressBar />
                    : null
                }
                <div className='grid lg:grid-cols-2 gap-2 my-2'>
                    <div>
                        <h3>Building Queues</h3>
                        <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3'></div>
                        <div className='max-h-[350px] overflow-y-scroll'>
                            {this.renderBuildingQueues()}
                        </div>
                    </div>
                    <div>
                        <h3>Unit Queues</h3>
                        <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3'></div>
                        <div className='max-h-[350px] overflow-y-scroll'>
                            {this.renderUnitRecruitmentQueues()}
                        </div>
                    </div>
                </div>
                <div className='grid lg:grid-cols-2 gap-2'>
                    <div>
                        <h3>Unit Movement</h3>
                        <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3'></div>
                        <div className='max-h-[350px] overflow-y-scroll'>
                            {this.renderUnitMovementQueues()}
                        </div>
                    </div>
                    <div>
                        <h3>Building Expansions</h3>
                        <div className='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3'></div>
                        <div className='max-h-[350px] overflow-y-scroll'>
                            {this.renderBuildingExpansionQueues()}
                        </div>
                    </div>
                </div>
            </div>
        );
    }
}
