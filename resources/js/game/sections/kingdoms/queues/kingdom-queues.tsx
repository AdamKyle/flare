import React, {ReactNode} from "react";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import {KingdomQueueProps} from "./types/kingdom-queue-props";
import KingdomQueueState, {
    BuildingExpansionQueue,
    BuildingQueue,
    UnitQueue
} from "./types/kingdom-queue-state";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import UnitMovementDetails from "./deffinitions/unit-movement-details";
import Ajax from "../../../lib/ajax/ajax";
import {AxiosError, AxiosResponse} from "axios";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import BasicCard from "../../../components/ui/cards/basic-card";
import DangerOutlineButton from "../../../components/ui/buttons/danger-outline-button";
import {serviceContainer} from "../../../lib/containers/core-container";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import {Channel} from "laravel-echo";

export default class KingdomQueues extends React.Component<KingdomQueueProps, KingdomQueueState> {

    private gameEventListener: CoreEventListener;

    private updateKingdomQueues: Channel;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            error_message: null,
            queues: null,
        }

        this.gameEventListener = serviceContainer().fetch(CoreEventListener);

        this.gameEventListener.initialize();

        this.updateKingdomQueues = this.gameEventListener.getEcho().private("refresh-kingdom-queues-" + this.props.user_id);
    }

    componentDidMount() {
        (new Ajax()).setRoute('kingdom/queues/'+this.props.kingdom_id+'/' + this.props.character_id)
            .doAjaxCall('get', (result: AxiosResponse) => {
                this.setState({
                    loading: false,
                    queues: result.data.queues,
                });
            }, (error: AxiosError) => {

                this.setState({
                    loading: false,
                });

                if (typeof error.response !== 'undefined') {
                    const result = error.response;

                    this.setState({
                        error_message: result.data.message,
                    });
                }
            });

        this.updateKingdomQueues.listen(
            "Game.Kingdoms.Events.UpdateKingdomQueues",
            (event: any) => {
                if (this.props.kingdom_id !== event.kingdomId) {
                    return;
                }

                this.setState({
                    queues: event.kingdomQueues
                });
            }
        );
    }

    renderBuildingQueues(): ReactNode[]|[]|null {
        if (this.state.queues === null) {
            return null;
        }

        const buildingQueues = this.state.queues.building_queues.map((buildingQueue: BuildingQueue) => {

            if (buildingQueue.type === 'upgrading') {
                return (
                    <BasicCard additionalClasses={'my-2'}>
                        <div className='bold my-4 text-gray-800 dark:text-gray-300'>
                            Upgrading {buildingQueue.name}
                        </div>
                        <TimerProgressBar  time_out_label={
                            'From Level: ' + buildingQueue.from_level + ' To Level: ' + buildingQueue.to_level
                        } time_remaining={buildingQueue.time_remaining} />
                        <DangerOutlineButton button_label={'Cancel'} on_click={() => {}} additional_css={'my-2'} />
                    </BasicCard>
                )
            }

            if (buildingQueue.type === 'repairing') {
                return (
                    <BasicCard additionalClasses={'my-2'}>
                        <div className='bold my-2'>
                            Repairing {buildingQueue.name}
                        </div>
                        <TimerProgressBar time_out_label={'Repairing'} time_remaining={buildingQueue.time_remaining} />
                        <DangerOutlineButton button_label={'Cancel'} on_click={() => {}} additional_css={'my-2'} />
                    </BasicCard>
                )
            }
        }).filter((buildingQueueData: ReactNode | undefined) => {
            return typeof buildingQueueData !== 'undefined'
        });

        return buildingQueues;
    }

    renderUnitRecruitmentQueues(): ReactNode[]|[]|null {
        if (this.state.queues === null) {
            return null;
        }

        return this.state.queues.unit_recruitment_queues.map((unitRecruitmentQueue: UnitQueue) => {
            return (
                <BasicCard additionalClasses={'my-2'}>
                    <div className='bold my-2'>
                        Recruiting {unitRecruitmentQueue.name}
                    </div>
                    <TimerProgressBar time_out_label={
                        'Rectuiting: ' + unitRecruitmentQueue.recruit_amount
                    } time_remaining={unitRecruitmentQueue.time_remaining} />
                    <DangerOutlineButton button_label={'Cancel'} on_click={() => {}} additional_css={'my-2'} />
                </BasicCard>
            )
        });
    }

    renderUnitMovementQueues(): ReactNode[]|[]|null {
        if (this.state.queues === null) {
            return null;
        }

        return this.state.queues.unit_movement_queues.map((unitMovementQueue: UnitMovementDetails) => {
            return (
                <BasicCard additionalClasses={'my-2'}>
                    <div className='bold my-2'>
                        Units Are on the move!
                    </div>
                    <p className='my-2'>
                        <strong>Why?</strong> {unitMovementQueue.reason}
                    </p>
                    <TimerProgressBar time_out_label={
                        'Units are in movement'
                    } time_remaining={unitMovementQueue.time_left} />
                    <DangerOutlineButton button_label={'Cancel'} on_click={() => {}} additional_css={'my-2'} />
                </BasicCard>
            )
        });
    }

    renderBuildingExpansionQueues(): ReactNode[]|[]|null {

        if (this.state.queues === null) {
            return null;
        }

        return this.state.queues.building_expansion_queues.map((buildingExpansionQueue: BuildingExpansionQueue) => {
            return (
                <BasicCard additionalClasses={'my-2'}>
                    <div className='bold my-2'>
                        {buildingExpansionQueue.name} Is expanding production
                    </div>
                    <TimerProgressBar time_out_label={
                        'From slot: ' + buildingExpansionQueue.from_slot + ' to slot: ' + buildingExpansionQueue.to_slot
                    } time_remaining={buildingExpansionQueue.time_remaining} />
                    <DangerOutlineButton button_label={'Cancel'} on_click={() => {}} additional_css={'my-2'} />
                </BasicCard>
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
                {
                    this.state.error_message !== null ?
                        <DangerAlert>
                            {this.state.error_message}
                        </DangerAlert>
                    : null
                }
                <div className='w-[90%] mr-auto ml-auto max-h-[600px] overflow-y-auto'>
                    {this.renderBuildingQueues()}
                    {this.renderBuildingExpansionQueues()}
                    {this.renderUnitRecruitmentQueues()}
                    {this.renderUnitMovementQueues()}
                </div>
            </div>
        );
    }
}
