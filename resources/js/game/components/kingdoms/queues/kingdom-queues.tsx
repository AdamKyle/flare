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
    UnitMovementQueues,
} from "./types/kingdom-queue-state";
import KingdomDetails from "../deffinitions/kingdom-details";

const QUEUE_ROWS_PER_PAGE = 10;

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
            queue_page: 1,
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
                        queue_page: 1,
                    });
                },
                (error: AxiosError) => {
                    this.setState({
                        loading: false,
                    });

                    if (typeof error.response !== "undefined") {
                        const result: AxiosResponse = error.response;

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
                    queue_page: 1,
                });
            },
        );
    }

    cancelQueue(
        cancellationType: CancellationType,
        queueIndex: number,
        queueKey: QueueTypes,
    ) {
        if (this.props.is_automation_locked || this.state.queues === null) {
            return;
        }

        if (this.state.queues[queueKey][queueIndex].is_capital_city_managed) {
            return;
        }

        this.cancellationAjax.doAjaxCall(
            this,
            cancellationType,
            this.state.queues[queueKey][queueIndex],
            this.props.character_id,
        );
    }

    renderBuildingQueue(
        buildingQueue: BuildingQueue,
        index: number,
    ): ReactNode | null {
        if (buildingQueue.is_capital_city_managed) {
            return (
                <BasicCard additionalClasses={"my-2"}>
                    <div className="bold my-4 text-gray-800 dark:text-gray-300">
                        {buildingQueue.type} {buildingQueue.name}
                    </div>
                    <TimerProgressBar
                        time_out_label={
                            buildingQueue.from_level !== null &&
                            buildingQueue.to_level !== null
                                ? "From Level: " +
                                  buildingQueue.from_level +
                                  " To Level: " +
                                  buildingQueue.to_level
                                : buildingQueue.type
                        }
                        time_remaining={buildingQueue.time_remaining}
                    />
                    <div className="mb-2 mt-4 text-sm text-gray-700 dark:text-gray-300">
                        Managed by Capital City. Cannot be cancelled here.
                    </div>
                </BasicCard>
            );
        }

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
                        disabled={this.props.is_automation_locked}
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
                        disabled={this.props.is_automation_locked}
                    />
                </BasicCard>
            );
        }

        return null;
    }

    renderUnitRecruitmentQueue(
        unitRecruitmentQueue: UnitQueue,
        index: number,
    ): ReactNode {
        return (
            <BasicCard additionalClasses={"my-2"}>
                <div className="bold my-2">
                    {unitRecruitmentQueue.is_capital_city_managed
                        ? unitRecruitmentQueue.type
                        : "Recruiting"}{" "}
                    {unitRecruitmentQueue.name}
                </div>
                <TimerProgressBar
                    time_out_label={
                        "Recruiting: " + unitRecruitmentQueue.recruit_amount
                    }
                    time_remaining={unitRecruitmentQueue.time_remaining}
                />
                {!unitRecruitmentQueue.is_capital_city_managed ? (
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
                        disabled={this.props.is_automation_locked}
                    />
                ) : null}
            </BasicCard>
        );
    }

    renderUnitMovementQueue(
        unitMovementQueue: UnitMovementDetails,
        index: number,
    ): ReactNode {
        let canCancelAttack = true;

        if (unitMovementQueue.reason === "Currently attacking") {
            canCancelAttack =
                this.props.kingdoms.filter((kingdom: KingdomDetails) => {
                    return kingdom.name === unitMovementQueue.from_kingdom_name;
                }).length > 0;
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
                        {unitMovementQueue.from_x}/{unitMovementQueue.from_y})
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
                        this.props.is_automation_locked ||
                        ((!canCancelAttack || !canCancelRecall) &&
                            unitMovementQueue.reason ===
                                "Returning from attack") ||
                        unitMovementQueue.reason === "Recalled units"
                    }
                />
            </BasicCard>
        );
    }

    renderBuildingExpansionQueue(
        buildingExpansionQueue: BuildingExpansionQueue,
        index: number,
    ): ReactNode {
        return (
            <BasicCard additionalClasses={"my-2"}>
                <div className="bold my-2">
                    {buildingExpansionQueue.name} Is expanding production
                </div>
                <TimerProgressBar
                    time_out_label={
                        "From slot: " +
                        buildingExpansionQueue.from_slot +
                        " to slot: " +
                        buildingExpansionQueue.to_slot
                    }
                    time_remaining={buildingExpansionQueue.time_remaining}
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
                    disabled={this.props.is_automation_locked}
                />
            </BasicCard>
        );
    }

    renderQueueRows(): ReactNode[] | null {
        if (this.state.queues === null) {
            return null;
        }

        const queueRows: ReactNode[] = [];

        this.state.queues.building_queues.forEach(
            (buildingQueue: BuildingQueue, index: number) => {
                const queueRow = this.renderBuildingQueue(buildingQueue, index);

                if (queueRow !== null) {
                    queueRows.push(queueRow);
                }
            },
        );

        this.state.queues.building_expansion_queues.forEach(
            (buildingExpansionQueue: BuildingExpansionQueue, index: number) => {
                queueRows.push(
                    this.renderBuildingExpansionQueue(
                        buildingExpansionQueue,
                        index,
                    ),
                );
            },
        );

        this.state.queues.unit_recruitment_queues.forEach(
            (unitRecruitmentQueue: UnitQueue, index: number) => {
                queueRows.push(
                    this.renderUnitRecruitmentQueue(
                        unitRecruitmentQueue,
                        index,
                    ),
                );
            },
        );

        this.state.queues.unit_movement_queues.forEach(
            (unitMovementQueue: UnitMovementQueues, index: number) => {
                queueRows.push(
                    this.renderUnitMovementQueue(unitMovementQueue, index),
                );
            },
        );

        const page = Math.min(
            this.state.queue_page,
            Math.max(1, Math.ceil(queueRows.length / QUEUE_ROWS_PER_PAGE)),
        );
        const startIndex = (page - 1) * QUEUE_ROWS_PER_PAGE;

        return queueRows.slice(startIndex, startIndex + QUEUE_ROWS_PER_PAGE);
    }

    renderQueuePagination(): ReactNode | null {
        if (this.state.queues === null) {
            return null;
        }

        const totalQueues =
            this.state.queues.building_queues.length +
            this.state.queues.building_expansion_queues.length +
            this.state.queues.unit_recruitment_queues.length +
            this.state.queues.unit_movement_queues.length;
        const totalPages = Math.ceil(totalQueues / QUEUE_ROWS_PER_PAGE);

        if (totalPages <= 1) {
            return null;
        }

        const currentPage = Math.min(this.state.queue_page, totalPages);

        return (
            <div className="flex items-center justify-between gap-3 my-3 shrink-0">
                <button
                    type="button"
                    className="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 disabled:opacity-50 dark:border-gray-600 dark:text-gray-300"
                    disabled={currentPage <= 1}
                    onClick={() => {
                        this.setState({
                            queue_page: Math.max(1, currentPage - 1),
                        });
                    }}
                >
                    Previous
                </button>
                <span className="text-sm text-gray-700 dark:text-gray-300">
                    Page {currentPage} of {totalPages}
                </span>
                <button
                    type="button"
                    className="rounded border border-gray-300 px-3 py-2 text-sm text-gray-700 disabled:opacity-50 dark:border-gray-600 dark:text-gray-300"
                    disabled={currentPage >= totalPages}
                    onClick={() => {
                        this.setState({
                            queue_page: Math.min(totalPages, currentPage + 1),
                        });
                    }}
                >
                    Next
                </button>
            </div>
        );
    }

    render() {
        return (
            <div className="h-full min-h-0 flex flex-col">
                <p className="my-2 shrink-0">
                    Below you will find the various queues. This could be
                    building expansions, repairs, upgrades, unit recruitment and
                    movement.
                </p>
                <div className="border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3 shrink-0"></div>
                {this.state.loading ? (
                    <div className="shrink-0">
                        <LoadingProgressBar />
                    </div>
                ) : null}
                {this.state.error_message !== null ? (
                    <div className="shrink-0">
                        <DangerAlert>{this.state.error_message}</DangerAlert>
                    </div>
                ) : null}
                {this.state.success_message !== null ? (
                    <div className="shrink-0">
                        <SuccessAlert>
                            {this.state.success_message}
                        </SuccessAlert>
                    </div>
                ) : null}
                <div className="w-full flex-1 min-h-0 overflow-y-auto">
                    {this.renderQueueRows()}
                </div>
                {this.renderQueuePagination()}
            </div>
        );
    }
}
