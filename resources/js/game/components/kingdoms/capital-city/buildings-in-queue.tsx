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

export default class BuildingsInQueue extends React.Component<any, any> {
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
            show_cancellation_modal: false,
            building_data_for_cancellation: null,
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
        this.setState((prevState: any) => {
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

        const openKingdomIds = new Set<number>();

        let filteredBuildingData = this.state.building_queues.filter(
            (kingdom: any) => {
                return (
                    (kingdom.kingdom_name.toLowerCase().includes(searchTerm) ||
                        kingdom.map_name.toLowerCase().includes(searchTerm)) &&
                    kingdom.building_queue.length > 0
                );
            },
        );

        if (filteredBuildingData.length <= 0 && searchTerm.length > 0) {
            filteredBuildingData = this.state.building_queues
                .map((kingdom: any) => {
                    if (kingdom === null) {
                        return null;
                    }

                    const matchingBuildings = kingdom.building_queue.filter(
                        (buildingQueue: any) => {
                            return buildingQueue.building_name
                                .toLowerCase()
                                .includes(searchTerm);
                        },
                    );

                    if (matchingBuildings.length > 0) {
                        openKingdomIds.add(kingdom.kingdom_id);

                        return {
                            ...kingdom,
                            building_queue: matchingBuildings,
                        };
                    }
                })
                .filter((kingdom: any) => kingdom !== null);
        }

        this.setState({
            filtered_building_queues: filteredBuildingData,
            open_kingdom_ids: openKingdomIds,
        });
    }

    handleSearchChange(event: React.ChangeEvent<HTMLInputElement>) {
        const searchTerm = event.target.value;
        this.setState({ search_query: searchTerm });
        this.debouncedUpdateFilteredData();
    }

    debouncedUpdateFilteredData = debounce(() => {
        this.updateFilteredBuildingData();
    }, 300);

    manageCancelModal(buildingId?: number): void {
        let buildingData: any = null;

        if (buildingId) {
            const foundData = this.state.building_queues.flatMap(
                (queueGroup: any) =>
                    queueGroup.building_queue.filter(
                        (queue: any) => queue.building_id === buildingId,
                    ),
            );

            if (foundData.length > 0) {
                const queueGroup = this.state.building_queues.find(
                    (queueGroup: any) =>
                        queueGroup.building_queue.some(
                            (queue: any) => queue.building_id === buildingId,
                        ),
                );

                if (queueGroup) {
                    buildingData = {
                        ...foundData[0],
                        kingdom_id: queueGroup.kingdom_id,
                        kingdom_name: queueGroup.kingdom_name,
                    };
                }
            }
        }

        this.setState({
            show_cancellation_modal: !this.state.show_cancellation_modal,
            building_data_for_cancellation: buildingData,
        });
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

                <input
                    type="text"
                    value={this.state.search_query}
                    onChange={(e) => this.handleSearchChange(e)}
                    placeholder="Search by kingdom or building name"
                    className="w-full my-4 px-4 py-2 border rounded text-gray-900 dark:text-white bg-white dark:bg-gray-700 border-gray-300 dark:border-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    aria-label="Search by kingdom or building name"
                />

                {this.state.filtered_building_queues.map((queueGroup: any) => (
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
                                        Status: {capitalize(queueGroup.status)}
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
                                {queueGroup.building_queue.map((queue: any) => (
                                    <div
                                        key={queue.queue_id}
                                        className="mb-4 p-4 bg-white dark:bg-gray-800 shadow-sm rounded-lg"
                                    >
                                        <h3 className="text-lg font-semibold dark:text-white">
                                            {queue.building_name}
                                        </h3>
                                        <p className="text-gray-700 dark:text-gray-300">
                                            Status:{" "}
                                            {capitalize(queue.secondary_status)}
                                        </p>
                                        <DangerOutlineButton
                                            on_click={() =>
                                                this.manageCancelModal(
                                                    queue.building_id,
                                                )
                                            }
                                            button_label={"Cancel Upgrade"}
                                            disabled={
                                                queueGroup.total_time < 60
                                            }
                                        />
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                ))}

                {this.state.show_cancellation_modal && (
                    <SendBuildingUpgradeCancellationRequestModal
                        is_open={this.state.show_cancellation_modal}
                        manage_modal={this.manageCancelModal.bind(this)}
                        queue_data={this.state.building_data_for_cancellation}
                        character_id={this.props.character_id}
                    />
                )}
            </div>
        );
    }
}
