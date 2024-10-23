import React from "react";
import FetchBuildingQueuesAjax from "../ajax/fetch-building-queues-ajax";
import { serviceContainer } from "../../../lib/containers/core-container";
import LoadingProgressBar from "../../ui/progress-bars/loading-progress-bar";
import SuccessAlert from "../../ui/alerts/simple-alerts/success-alert";
import DangerAlert from "../../ui/alerts/simple-alerts/danger-alert";
import debounce from "lodash/debounce";
import SendBuildingUpgradeCancellationRequestModal from "./modals/send-building-upgrade-cancellation-request-modal";
import CapitalCityBuildingQueueTableEventDefinition from "../event-listeners/capital-city-building-queue-table-event-definition";
import CapitalCityBuildingQueuesTableEvent from "../event-listeners/capital-city-building-queues-table-event";
import { viewPortWatcher } from "../../../lib/view-port-watcher";
import { watchForDarkMode } from "../../ui/helpers/watch-for-dark-mode";
import clsx from "clsx";
import TimerProgressBar from "../../ui/progress-bars/timer-progress-bar";
import { capitalize } from "lodash";
import DangerOutlineButton from "../../ui/buttons/danger-outline-button";
import { QueueStatus } from "./enums/queue-status";
import OrangeButton from "../../ui/buttons/orange-button";
import InfoAlert from "../../ui/alerts/simple-alerts/info-alert";
import { CancellationType } from "./enums/cancellation-type";
import BuildingInQueueProps from "./types/building-in-queue-props";
import BuildingInQueueState from "./types/building-in-queue-state";
import KingddomBuildingQueue from "./deffinitions/kingdom-building-queue";
import BuildingInQueue from "./deffinitions/building-in-queue";

export default class BuildingsInQueue extends React.Component<
    BuildingInQueueProps,
    BuildingInQueueState
> {
    private fetchBuildingQueueAjax: FetchBuildingQueuesAjax;
    private queueListener: CapitalCityBuildingQueueTableEventDefinition;

    constructor(props: any) {
        super(props);

        this.state = {
            loading: true,
            success_message: null,
            error_message: null,
            building_queues: [],
            filtered_building_queues: [],
            search_query: "",
            open_kingdom_ids: new Set<number>(),
            view_port: 0,
            dark_tables: false,
            cancelation_modal: {
                open: false,
                building_details: null,
                kingdom_id: null,
                queue_id: null,
                cancellation_type: null,
            },
        };

        this.fetchBuildingQueueAjax = serviceContainer().fetch(
            FetchBuildingQueuesAjax,
        );

        this.queueListener =
            serviceContainer().fetch<CapitalCityBuildingQueueTableEventDefinition>(
                CapitalCityBuildingQueuesTableEvent,
            );

        this.queueListener.initialize(this, this.props.user_id);
        this.queueListener.register();
    }

    componentDidMount() {
        viewPortWatcher(this);
        watchForDarkMode(this);

        this.fetchBuildingQueueAjax.fetchQueueData(
            this,
            this.props.character_id,
            this.props.kingdom_id,
        );

        this.queueListener.listen();
    }

    componentDidUpdate(prevProps: any, prevState: any) {
        if (prevState.building_queues !== this.state.building_queues) {
            this.updateFilteredBuildingData();
        }
    }

    toggleDetails(kingdomId: number) {
        this.setState((prevState: BuildingInQueueState) => {
            const newOpenKingdomIds = new Set(prevState.open_kingdom_ids);
            if (newOpenKingdomIds.has(kingdomId)) {
                newOpenKingdomIds.delete(kingdomId);
            } else {
                newOpenKingdomIds.add(kingdomId);
            }
            return { open_kingdom_ids: newOpenKingdomIds };
        });
    }

    updateFilteredBuildingData() {
        const searchTerm = this.state.search_query.toLowerCase().trim();
        let openKingdomIds = new Set<number>();

        if (searchTerm === "") {
            this.setState({
                filtered_building_queues: this.state.building_queues,
                open_kingdom_ids: this.state.open_kingdom_ids,
            });

            return;
        }

        let filteredBuildingData = this.state.building_queues.filter(
            (kingdom: any) =>
                (kingdom.kingdom_name.toLowerCase().includes(searchTerm) ||
                    kingdom.map_name.toLowerCase().includes(searchTerm)) &&
                kingdom.building_queue.length > 0,
        );

        if (filteredBuildingData.length <= 0 && searchTerm.length > 0) {
            filteredBuildingData = this.state.building_queues
                .map((kingdom: any) => {
                    if (!kingdom) return null;

                    const matchingBuildings = kingdom.building_queue.filter(
                        (buildingQueue: any) =>
                            buildingQueue.building_name
                                .toLowerCase()
                                .includes(searchTerm),
                    );

                    if (matchingBuildings.length > 0) {
                        openKingdomIds.add(kingdom.kingdom_id);
                        return {
                            ...kingdom,
                            building_queue: matchingBuildings,
                        };
                    }
                    return null;
                })
                .filter((kingdom: any) => kingdom !== null);
        }

        console.log(filteredBuildingData);

        if (filteredBuildingData.length === 0) {
            filteredBuildingData = this.state.building_queues;
            openKingdomIds = new Set<number>();
        }

        if (openKingdomIds.size > 0) {
            openKingdomIds = this.resolveOpenKingdomIds(openKingdomIds);
        }

        this.setState({
            filtered_building_queues: filteredBuildingData,
            open_kingdom_ids: openKingdomIds,
        });
    }

    resolveOpenKingdomIds(newOpenKingdomIds: Set<number>): Set<number> {
        const oldOpenKingdomIds = this.state.open_kingdom_ids;

        if (newOpenKingdomIds.size === 0 && oldOpenKingdomIds.size > 0) {
            oldOpenKingdomIds.forEach((id: number) => {
                const kingdomExists = this.state.building_queues.some(
                    (kingdom: any) => kingdom.kingdom_id === id,
                );
                if (!kingdomExists) {
                    oldOpenKingdomIds.delete(id);
                }
            });
            return oldOpenKingdomIds;
        }

        if (newOpenKingdomIds.size === oldOpenKingdomIds.size) {
            return oldOpenKingdomIds;
        }

        return newOpenKingdomIds.size > oldOpenKingdomIds.size
            ? newOpenKingdomIds
            : oldOpenKingdomIds;
    }

    handleSearchChange(event: React.ChangeEvent<HTMLInputElement>) {
        const searchTerm = event.target.value;
        this.setState({ search_query: searchTerm });

        if (searchTerm.length <= 0) {
            this.setState({
                filtered_building_queues: this.state.building_queues,
                open_kingdom_ids: new Set<number>(),
            });

            return;
        }

        this.debouncedUpdateFilteredData();
    }

    debouncedUpdateFilteredData = debounce(() => {
        this.updateFilteredBuildingData();
    }, 300);

    openCancelModal(
        kingdomId: number,
        queueId: number,
        cancelationType: CancellationType,
        buildingDetails?: any,
    ): void {
        this.setState({
            cancelation_modal: {
                open: true,
                building_details: buildingDetails ?? null,
                kingdom_id: kingdomId,
                queue_id: queueId,
                cancellation_type: cancelationType,
            },
        });
    }

    closeCancelModal() {
        this.setState({
            cancelation_modal: {
                open: false,
                building_details: null,
                kingdom_id: null,
                queue_id: null,
                cancellation_type: null,
            },
        });
    }

    disableCancelBuildingButton(
        queue: KingddomBuildingQueue,
        status: QueueStatus,
    ): boolean {
        if (queue.total_time <= 60) {
            return true;
        }

        return status !== QueueStatus.TRAVELING;
    }

    disableCancelAllButton(queue: KingddomBuildingQueue): boolean {
        if (this.state.search_query.length > 0) {
            return true;
        }

        return queue.total_time <= 60;
    }

    render() {
        if (this.state.loading) {
            return <LoadingProgressBar />;
        }

        return (
            <div className="md:p-4">
                {this.state.success_message && (
                    <SuccessAlert>{this.state.success_message}</SuccessAlert>
                )}
                {this.state.error_message && (
                    <DangerAlert>{this.state.error_message}</DangerAlert>
                )}

                {this.state.filtered_building_queues.length <= 0 ? (
                    <p>There is nothing in queue.</p>
                ) : (
                    <input
                        type="text"
                        value={this.state.search_query}
                        onChange={(e) => this.handleSearchChange(e)}
                        placeholder="Search by kingdom or building name"
                        className="w-full my-4 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        aria-label="Search by kingdom or building name"
                    />
                )}

                {this.state.filtered_building_queues.map(
                    (queueGroup: KingddomBuildingQueue) => (
                        <div key={queueGroup.kingdom_id} className="mb-4">
                            <div
                                className={clsx(
                                    "p-4 bg-gray-100 dark:bg-gray-700 shadow-md cursor-pointer",
                                    {
                                        "rounded-lg":
                                            !this.state.open_kingdom_ids.has(
                                                queueGroup.kingdom_id,
                                            ),
                                        "rounded-t-lg":
                                            this.state.open_kingdom_ids.has(
                                                queueGroup.kingdom_id,
                                            ),
                                    },
                                )}
                                onClick={() =>
                                    this.toggleDetails(queueGroup.kingdom_id)
                                }
                            >
                                <div className="flex justify-between items-center">
                                    <div>
                                        <h2 className="text-xl font-bold dark:text-white">
                                            {queueGroup.kingdom_name}
                                        </h2>
                                        <p className="text-gray-700 dark:text-gray-300">
                                            Status:{" "}
                                            <span
                                                className={clsx({
                                                    "text-green-700 dark:text-green-500":
                                                        [
                                                            QueueStatus.TRAVELING,
                                                            QueueStatus.BUILDING,
                                                            QueueStatus.FINISHED,
                                                        ].includes(
                                                            queueGroup.status,
                                                        ),
                                                    "text-red-700 dark:text-red-500":
                                                        [
                                                            QueueStatus.REJECTED,
                                                            QueueStatus.CANCELLED,
                                                        ].includes(
                                                            queueGroup.status,
                                                        ),
                                                    "text-blue-700 dark:text-500":
                                                        [
                                                            QueueStatus.REQUESTING,
                                                            QueueStatus.PROCESSING,
                                                        ].includes(
                                                            queueGroup.status,
                                                        ),
                                                })}
                                            >
                                                {capitalize(queueGroup.status)}
                                            </span>
                                        </p>
                                    </div>
                                    <i
                                        className={`fas fa-chevron-${
                                            this.state.open_kingdom_ids.has(
                                                queueGroup.kingdom_id,
                                            )
                                                ? "down"
                                                : "up"
                                        } text-gray-500 dark:text-gray-400`}
                                    />
                                </div>
                                <TimerProgressBar
                                    time_remaining={queueGroup.total_time}
                                    time_out_label={"Total Time Left"}
                                />
                            </div>
                            {this.state.open_kingdom_ids.has(
                                queueGroup.kingdom_id,
                            ) && (
                                <div className="bg-gray-300 dark:bg-gray-600 p-4">
                                    <InfoAlert additional_css="my-2">
                                        <p className="mb-2">
                                            You may only cancel building
                                            upgrade/repair requests when the
                                            order is traveling and has at least
                                            more then 1 minute in time left.
                                            Trying to do so at any other time
                                            such as requesting resources or
                                            recruiting can throw the kingdom
                                            into chaos.
                                        </p>
                                        <p>
                                            Buldings that are canceled or
                                            rejected will be at the bottom of
                                            the list. Once the entire process is
                                            finished, the log will indicate why
                                            some were rejected or that you
                                            canceled some building requests.
                                        </p>
                                    </InfoAlert>
                                    <OrangeButton
                                        button_label={"Cancel All Buildings"}
                                        on_click={() => {
                                            this.openCancelModal(
                                                queueGroup.kingdom_id,
                                                queueGroup.queue_id,
                                                CancellationType.CANCEL_ALL,
                                            );
                                        }}
                                        additional_css="my-4 w-full"
                                        disabled={this.disableCancelAllButton(
                                            queueGroup,
                                        )}
                                    ></OrangeButton>
                                    {queueGroup.building_queue.map(
                                        (queue: BuildingInQueue) => (
                                            <div
                                                key={queue.building_id}
                                                className="mb-4 p-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg"
                                            >
                                                <h3 className="text-lg font-semibold dark:text-white">
                                                    {queue.building_name}
                                                </h3>
                                                <p className="text-gray-700 dark:text-gray-300 my-2">
                                                    From level to Level:{" "}
                                                    {queue.from_level}{" "}
                                                    <i className="fas fa-arrow-right mx-2"></i>{" "}
                                                    {queue.to_level}
                                                </p>
                                                <p className="text-gray-700 dark:text-gray-300 mb-2">
                                                    Status:{" "}
                                                    <span
                                                        className={clsx({
                                                            "text-green-700 dark:text-green-500":
                                                                [
                                                                    QueueStatus.TRAVELING,
                                                                    QueueStatus.BUILDING,
                                                                    QueueStatus.FINISHED,
                                                                ].includes(
                                                                    queue.secondary_status,
                                                                ),
                                                            "text-red-700 dark:text-red-500":
                                                                [
                                                                    QueueStatus.REJECTED,
                                                                    QueueStatus.CANCELLED,
                                                                ].includes(
                                                                    queue.secondary_status,
                                                                ),
                                                            "text-blue-700 dark:text-500":
                                                                [
                                                                    QueueStatus.REQUESTING,
                                                                ].includes(
                                                                    queue.secondary_status,
                                                                ),
                                                        })}
                                                    >
                                                        {capitalize(
                                                            queue.secondary_status,
                                                        )}
                                                    </span>
                                                </p>
                                                <DangerOutlineButton
                                                    on_click={() =>
                                                        this.openCancelModal(
                                                            queueGroup.kingdom_id,
                                                            queueGroup.queue_id,
                                                            CancellationType.SINGLE_CANCEL,
                                                            queue,
                                                        )
                                                    }
                                                    button_label={
                                                        "Cancel Upgrade"
                                                    }
                                                    disabled={this.disableCancelBuildingButton(
                                                        queueGroup,
                                                        queue.secondary_status,
                                                    )}
                                                />
                                            </div>
                                        ),
                                    )}
                                </div>
                            )}
                        </div>
                    ),
                )}

                {this.state.cancelation_modal.open && (
                    <SendBuildingUpgradeCancellationRequestModal
                        kingdom_id={this.state.cancelation_modal.kingdom_id}
                        character_id={this.props.character_id}
                        is_open={this.state.cancelation_modal.open}
                        manage_modal={this.closeCancelModal.bind(this)}
                        cancellation_type={
                            this.state.cancelation_modal.cancellation_type
                        }
                        queue_id={this.state.cancelation_modal.queue_id}
                        building_details={
                            this.state.cancelation_modal.building_details
                        }
                    />
                )}
            </div>
        );
    }
}
