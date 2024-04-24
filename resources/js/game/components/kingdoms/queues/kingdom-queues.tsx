import { AxiosError, AxiosResponse } from "axios";
import { Channel } from "laravel-echo";
import React, { ReactNode } from "react";
import DangerAlert from "../../../components/ui/alerts/simple-alerts/danger-alert";
import SuccessAlert from "../../../components/ui/alerts/simple-alerts/success-alert";
import DangerOutlineButton from "../../../components/ui/buttons/danger-outline-button";
import BasicCard from "../../../components/ui/cards/basic-card";
import LoadingProgressBar from "../../../components/ui/progress-bars/loading-progress-bar";
import TimerProgressBar from "../../../components/ui/progress-bars/timer-progress-bar";
import Ajax from "../../../lib/ajax/ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import CoreEventListener from "../../../lib/game/event-listeners/core-event-listener";
import KingdomDetails from "../../../lib/game/kingdoms/deffinitions/kingdom-details";
import { unitMovementReasonIcon } from "../helpers/unit-movement-reason-icon";
import CancellationAjax from "./ajax/cancellation-ajax";
import UnitMovementDetails from "./deffinitions/unit-movement-details";
import { CancellationType } from "./enums/cancellation-type";
import { QueueTypes } from "./enums/queue-types";
import { KingdomQueueProps } from "./types/kingdom-queue-props";
import KingdomQueueState, {
    BuildingExpansionQueue,
    BuildingQueue,
    UnitQueue,
} from "./types/kingdom-queue-state";

export default class KingdomQueues extends React.Component<
    KingdomQueueProps,
    KingdomQueueState
> {
    private gameEventListener: CoreEventListener;

    private updateKingdomQueues: Channel;

    private cancellationAjax: CancellationAjax;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            error_message: null,
            success_message: null,
            queues: null,
        };

        this.gameEventListener = serviceContainer().fetch(CoreEventListener);

        this.cancellationAjax = serviceContainer().fetch(CancellationAjax);

        this.gameEventListener.initialize();

        this.updateKingdomQueues = this.gameEventListener
            .getEcho()
            .private("refresh-kingdom-queues-" + this.props.user_id);
    }

    componentDidMount() {
        new Ajax()
            .setRoute(
                "kingdom/queues/" +
                    this.props.kingdom_id +
                    "/" +
                    this.props.character_id,
            )
            .doAjaxCall(
                "get",
                (result: AxiosResponse) => {
                    this.setState({
                        loading: false,
                        queues: result.data.queues,
                    });
                },
                (error: AxiosError) => {
                    this.setState({
                        loading: false,
                    });

                    if (typeof error.response !== "undefined") {
                        const result = error.response;

                        this.setState({
                            error_message: result.data.message,
                        });
                    }
                },
            );

        this.updateKingdomQueues.listen(
            "Game.Kingdoms.Events.UpdateKingdomQueues",
            (event: any) => {
                if (this.props.kingdom_id !== event.kingdomId) {
                    return;
                }

                this.setState({
                    queues: event.kingdomQueues,
                });
            },
        );
    }

    cancelQueue(
        cancellationType: CancellationType,
        queueIndex: number,
        queueKey: QueueTypes,
    ) {
        if (this.state.queues === null) {
            return;
        }

        this.cancellationAjax.doAjaxCall(
            this,
            cancellationType,
            this.state.queues[queueKey][queueIndex],
            this.props.character_id,
        );
    }

    renderBuildingQueues(): ReactNode[] | [] | null {
        if (this.state.queues === null) {
            return null;
        }

        const buildingQueues = this.state.queues.building_queues
            .map((buildingQueue: BuildingQueue, index: number) => {
                if (buildingQueue.type === "upgrading") {
                    return (
                        <BasicCard additionalClasses={"my-2"}>
                            <div className="bold my-4 text-gray-800 dark:text-gray-300">
                                Upgrading {buildingQueue.name}
                            </div>
                            <TimerProgressBar
                                time_out_label={
                                    "From Level: " +
                                    buildingQueue.from_level +
                                    " To Level: " +
                                    buildingQueue.to_level
                                }
                                time_remaining={buildingQueue.time_remaining}
                            />
                            <DangerOutlineButton
                                button_label={"Cancel"}
                                on_click={() => {
                                    this.cancelQueue(
                                        CancellationType.BUILDING_IN_QUEUE,
                                        index,
                                        QueueTypes.BUILDING_QUEUES,
                                    );
                                }}
                                additional_css={"my-2"}
                            />
                        </BasicCard>
                    );
                }

                if (buildingQueue.type === "repairing") {
                    return (
                        <BasicCard additionalClasses={"my-2"}>
                            <div className="bold my-2">
                                Repairing {buildingQueue.name}
                            </div>
                            <TimerProgressBar
                                time_out_label={"Repairing"}
                                time_remaining={buildingQueue.time_remaining}
                            />
                            <DangerOutlineButton
                                button_label={"Cancel"}
                                on_click={() => {
                                    this.cancelQueue(
                                        CancellationType.BUILDING_IN_QUEUE,
                                        index,
                                        QueueTypes.BUILDING_QUEUES,
                                    );
                                }}
                                additional_css={"my-2"}
                            />
                        </BasicCard>
                    );
                }
            })
            .filter((buildingQueueData: ReactNode | undefined) => {
                return typeof buildingQueueData !== "undefined";
            });

        return buildingQueues;
    }

    renderUnitRecruitmentQueues(): ReactNode[] | [] | null {
        if (this.state.queues === null) {
            return null;
        }

        return this.state.queues.unit_recruitment_queues.map(
            (unitRecruitmentQueue: UnitQueue, index: number) => {
                return (
                    <BasicCard additionalClasses={"my-2"}>
                        <div className="bold my-2">
                            Recruiting {unitRecruitmentQueue.name}
                        </div>
                        <TimerProgressBar
                            time_out_label={
                                "Rectuiting: " +
                                unitRecruitmentQueue.recruit_amount
                            }
                            time_remaining={unitRecruitmentQueue.time_remaining}
                        />
                        <DangerOutlineButton
                            button_label={"Cancel"}
                            on_click={() => {
                                this.cancelQueue(
                                    CancellationType.UNIT_RECRUITMENT,
                                    index,
                                    QueueTypes.UNIT_RECRUITMENT_QUEUES,
                                );
                            }}
                            additional_css={"my-2"}
                        />
                    </BasicCard>
                );
            },
        );
    }

    renderUnitMovementQueues(): ReactNode[] | [] | null {
        if (this.state.queues === null) {
            return null;
        }

        return this.state.queues.unit_movement_queues.map(
            (unitMovementQueue: UnitMovementDetails, index: number) => {
                let canCancelAttack = true;

                if (unitMovementQueue.reason === "Currently attacking") {
                    canCancelAttack =
                        this.props.kingdoms.filter(
                            (kingdom: KingdomDetails) => {
                                return (
                                    kingdom.name ===
                                    unitMovementQueue.from_kingdom_name
                                );
                            },
                        ).length > 0;
                }

                let canCancelRecall = true;

                return (
                    <BasicCard additionalClasses={"my-2"}>
                        <div className="bold my-2">Units Are on the move!</div>
                        <dl className={"my-4"}>
                            <dt>Why</dt>
                            <dd>
                                {unitMovementReasonIcon(unitMovementQueue)}{" "}
                                {unitMovementQueue.reason}
                            </dd>
                            <dt>From:</dt>
                            <dd>
                                {unitMovementQueue.from_kingdom_name} (X/Y:{" "}
                                {unitMovementQueue.from_x}/
                                {unitMovementQueue.from_y})
                            </dd>
                            <dt>To:</dt>
                            <dd>
                                {unitMovementQueue.to_kingdom_name} (X/Y:{" "}
                                {unitMovementQueue.moving_to_y}/
                                {unitMovementQueue.moving_to_y})
                            </dd>
                        </dl>

                        <TimerProgressBar
                            time_out_label={"Units are in movement"}
                            time_remaining={unitMovementQueue.time_left}
                        />

                        <DangerOutlineButton
                            button_label={"Cancel"}
                            on_click={() => {
                                this.cancelQueue(
                                    CancellationType.UNIT_MOVEMENT,
                                    index,
                                    QueueTypes.UNIT_MOVEMENT_QUEUES,
                                );
                            }}
                            additional_css={"my-2"}
                            disabled={
                                ((!canCancelAttack || !canCancelRecall) &&
                                    unitMovementQueue.reason ===
                                        "Returning from attack") ||
                                unitMovementQueue.reason === "Recalled units"
                            }
                        />
                    </BasicCard>
                );
            },
        );
    }

    renderBuildingExpansionQueues(): ReactNode[] | [] | null {
        if (this.state.queues === null) {
            return null;
        }

        return this.state.queues.building_expansion_queues.map(
            (buildingExpansionQueue: BuildingExpansionQueue, index: number) => {
                return (
                    <BasicCard additionalClasses={"my-2"}>
                        <div className="bold my-2">
                            {buildingExpansionQueue.name} Is expanding
                            production
                        </div>
                        <TimerProgressBar
                            time_out_label={
                                "From slot: " +
                                buildingExpansionQueue.from_slot +
                                " to slot: " +
                                buildingExpansionQueue.to_slot
                            }
                            time_remaining={
                                buildingExpansionQueue.time_remaining
                            }
                        />
                        <DangerOutlineButton
                            button_label={"Cancel"}
                            on_click={() => {
                                this.cancelQueue(
                                    CancellationType.BUILDING_EXPANSION,
                                    index,
                                    QueueTypes.BUILDING_EXPANSION_QUEUES,
                                );
                            }}
                            additional_css={"my-2"}
                        />
                    </BasicCard>
                );
            },
        );
    }

    render() {
        return (
            <div>
                <p className="my-2">
                    Below you will find the various queues. This could be
                    building expansions, repairs, upgrades, unit recruitment and
                    movement.
                </p>
                <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3"></div>
                {this.state.loading ? <LoadingProgressBar /> : null}
                {this.state.error_message !== null ? (
                    <DangerAlert>{this.state.error_message}</DangerAlert>
                ) : null}
                {this.state.success_message !== null ? (
                    <SuccessAlert>{this.state.success_message}</SuccessAlert>
                ) : null}
                <div className="w-[90%] mr-auto ml-auto max-h-[600px] overflow-y-auto">
                    {this.renderBuildingQueues()}
                    {this.renderBuildingExpansionQueues()}
                    {this.renderUnitRecruitmentQueues()}
                    {this.renderUnitMovementQueues()}
                </div>
            </div>
        );
    }
}
